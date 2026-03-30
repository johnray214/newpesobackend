<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Jobseeker;
use App\Support\JobseekerPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Notification;
use App\Models\NotificationRead;

class JobseekerAuthController extends Controller
{
    private const OTP_TTL_MINUTES = 15;
    private const OTP_RESEND_COOLDOWN_BASE_SECONDS = 60;
    private const OTP_RESEND_COOLDOWN_MAX_SECONDS = 300;
    private const OTP_DAILY_LIMIT = 7;
    private const OTP_VERIFY_MAX_ATTEMPTS = 5;
    private const OTP_VERIFY_WINDOW_SECONDS = 15 * 60;
    private const UNVERIFIED_TTL_HOURS = 24;
    
    public function checkEmail(Request $request) {
        $request->validate(['email' => 'required|email']);
        $exists = Jobseeker::where('email', $request->email)->exists();
        return response()->json([
            'success' => true,
            'exists' => $exists,
            'message' => $exists ? 'Email is already registered.' : 'Email is available'
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:191',
            'password' => JobseekerPassword::createRules(),
            'contact' => 'required|string|regex:/^0\d{10}$/',
            'address' => 'nullable|string|max:255',
            'sex' => 'required|in:male,female',
            'date_of_birth' => 'nullable|date',
        ]);

        $existing = Jobseeker::where('email', $validated['email'])->first();
        if ($existing && $this->purgeIfExpiredUnverified($existing)) {
            $existing = null;
        }

        if ($existing && $existing->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already registered. Please sign in.',
            ], 422);
        }

        if ($existing && !$existing->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'requires_verification' => true,
                'message' => 'Email already registered but not verified yet. Please sign in to continue verification.',
            ], 409);
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'inactive';
        $validated['otp_code'] = null;
        $validated['otp_expires_at'] = null;
        $validated['otp_send_count_today'] = 0;
        $validated['otp_send_count_date'] = now()->toDateString();
        $validated['otp_resend_count'] = 0;
        $validated['otp_resend_cooldown_until'] = null;

        $jobseeker = Jobseeker::create($validated);

        $sendResult = $this->issueOtpWithGuardrails($jobseeker, false);
        if (!$sendResult['success']) {
            $jobseeker->delete();
            return response()->json([
                'success' => false,
                'message' => $sendResult['message'] ?? 'Registration failed because OTP email could not be sent. Please try again.',
            ], $sendResult['status'] ?? 500);
        }

        return response()->json([
            'success' => true,
            'requires_verification' => true,
            'message' => 'Registration successful. Enter the OTP sent to your email. Unverified accounts are deleted after 24 hours.',
            'remaining_daily_sends' => $sendResult['remaining_daily_sends'] ?? null,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $jobseeker = Jobseeker::where('email', $request->email)->first();

        if (!$jobseeker || !Hash::check($request->password, $jobseeker->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($this->purgeIfExpiredUnverified($jobseeker)) {
            return response()->json([
                'success' => false,
                'message' => 'This unverified account expired after 24 hours and was deleted. Please register again.',
            ], 410);
        }

        if (!$jobseeker->hasVerifiedEmail()) {
            $sendResult = $this->issueOtpWithGuardrails($jobseeker, true);
            return response()->json([
                'success' => false,
                'requires_verification' => true,
                'message' => 'Please verify your email with OTP first. Unverified accounts are deleted after 24 hours.',
                'retry_after_seconds' => $sendResult['retry_after_seconds'] ?? null,
                'remaining_daily_sends' => $sendResult['remaining_daily_sends'] ?? null,
            ], 403);
        }

        if ($jobseeker->status === 'inactive') {
            $jobseeker->status = 'active';
            $jobseeker->save();
        }

        $token = $jobseeker->createToken('jobseeker-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'jobseeker' => $jobseeker,
                'token' => $token,
            ],
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('skills'),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::exists('jobseekers', 'email')->whereNull('deleted_at'),
            ],
        ]);

        try {
            $status = Password::broker('jobseekers')->sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Jobseeker forgot password mail: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not send reset email. Please try again later.',
            ], 503);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['success' => true, 'message' => 'Password reset link sent to your email.']);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another reset link.',
            ], 429);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send reset link.'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => JobseekerPassword::createRules(),
        ]);

        $status = Password::broker('jobseekers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['success' => true, 'message' => 'Password has been securely reset.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to reset password, link might be invalid or expired.'], 400);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        $jobseeker = Jobseeker::where('email', $request->email)->first();

        if (!$jobseeker) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($jobseeker->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        if ($this->purgeIfExpiredUnverified($jobseeker)) {
            return response()->json([
                'success' => false,
                'message' => 'This unverified account expired after 24 hours and was deleted. Please register again.',
            ], 410);
        }

        $verifyKey = $this->verifyAttemptKey($request, $jobseeker->email);
        if (RateLimiter::tooManyAttempts($verifyKey, self::OTP_VERIFY_MAX_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many incorrect OTP attempts. Please wait before trying again.',
            ], 429);
        }

        if ($jobseeker->otp_code !== $request->otp_code) {
            RateLimiter::hit($verifyKey, self::OTP_VERIFY_WINDOW_SECONDS);
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (now()->greaterThan($jobseeker->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code has expired'], 400);
        }

        $jobseeker->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_resend_count' => 0,
            'otp_resend_cooldown_until' => null,
            'status' => 'active',
        ]);

        // CREATE WELCOME NOTIFICATION
        try {
            $notif = Notification::create([
                'subject' => "Welcome to PESO Connect!, {$jobseeker->first_name}! 🚀",
                'message' => "We're thrilled to have you here! PESO Connect! is dedicated to connecting you with the best career opportunities. To start your journey, please complete your profile during the onboarding process and explore jobs that match your skills. We're here to help you find your dream job!",
                'type' => 'welcome',
                'recipients' => 'specific',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            NotificationRead::create([
                'notification_id' => $notif->id,
                'recipient_type' => 'jobseeker',
                'recipient_id' => $jobseeker->id,
                'read_at' => null,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create welcome notification for jobseeker {$jobseeker->id}: " . $e->getMessage());
        }
        RateLimiter::clear($verifyKey);

        $token = $jobseeker->createToken('jobseeker-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'jobseeker' => $jobseeker,
                'token' => $token,
            ],
            'message' => 'Email verified successfully',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $jobseeker = Jobseeker::where('email', $request->email)->first();

        if (!$jobseeker) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($jobseeker->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        if ($this->purgeIfExpiredUnverified($jobseeker)) {
            return response()->json([
                'success' => false,
                'message' => 'This unverified account expired after 24 hours and was deleted. Please register again.',
            ], 410);
        }

        $sendResult = $this->issueOtpWithGuardrails($jobseeker, true);
        if (!$sendResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $sendResult['message'],
                'retry_after_seconds' => $sendResult['retry_after_seconds'] ?? null,
            ], $sendResult['status'] ?? 429);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
            'cooldown_seconds' => $sendResult['cooldown_seconds'] ?? null,
            'remaining_daily_sends' => $sendResult['remaining_daily_sends'] ?? null,
        ]);
    }

    private function sendOtpEmail(Jobseeker $jobseeker, string $otpCode): bool
    {
        try {
            $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => env('MAILJET_FROM_EMAIL', 'peso@posuechague.site'),
                            'Name'  => env('MAILJET_FROM_NAME', 'PESO')
                        ],
                        'To' => [
                            [
                                'Email' => $jobseeker->email,
                                'Name'  => $jobseeker->first_name
                            ]
                        ],
                        'TemplateID' => 7861324,
                        'TemplateLanguage' => true,
                        'Subject' => 'Verify Your Email — PESO Jobseeker',
                        'Variables' => [
                            'first_name' => $jobseeker->first_name,
                            'otp_code'   => $otpCode,
                            'verify_url' => env('FRONTEND_URL', 'http://localhost:8080') . '/jobseeker/verify-email?email=' . urlencode($jobseeker->email) . '&otp=' . $otpCode
                        ]
                    ]
                ]
            ];
            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
            if (!$response->success()) {
                Log::error('Mailjet API Error (Jobseeker OTP): ' . json_encode($response->getData()));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('Mailjet Exception (Jobseeker OTP): ' . $e->getMessage());
            return false;
        }
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function verifyAttemptKey(Request $request, string $email): string
    {
        return 'otp-verify:' . strtolower($email) . ':' . $request->ip();
    }

    private function issueOtpWithGuardrails(Jobseeker $jobseeker, bool $isResend): array
    {
        $now = Carbon::now();
        $this->refreshOtpDailyCounterWindow($jobseeker, $now);

        if ((int) $jobseeker->otp_send_count_today >= self::OTP_DAILY_LIMIT) {
            return [
                'success' => false,
                'status' => 429,
                'message' => 'Daily OTP limit reached (7/day). Please try again tomorrow.',
                'retry_after_seconds' => $this->secondsUntilNextDay($now),
                'remaining_daily_sends' => 0,
            ];
        }

        if (
            $isResend &&
            $jobseeker->otp_resend_cooldown_until &&
            $now->lt($jobseeker->otp_resend_cooldown_until)
        ) {
            return [
                'success' => false,
                'status' => 429,
                'message' => 'Please wait before requesting another OTP.',
                'retry_after_seconds' => $now->diffInSeconds($jobseeker->otp_resend_cooldown_until),
                'remaining_daily_sends' => max(0, self::OTP_DAILY_LIMIT - (int) $jobseeker->otp_send_count_today),
            ];
        }

        $otpCode = $this->generateOtp();
        if (!$this->sendOtpEmail($jobseeker, $otpCode)) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Failed to send OTP email. Please try again shortly.',
                'remaining_daily_sends' => max(0, self::OTP_DAILY_LIMIT - (int) $jobseeker->otp_send_count_today),
            ];
        }

        $cooldownSeconds = 0;
        $resendCount = (int) $jobseeker->otp_resend_count;
        if ($isResend) {
            $resendCount++;
            $cooldownSeconds = min(
                self::OTP_RESEND_COOLDOWN_BASE_SECONDS * $resendCount,
                self::OTP_RESEND_COOLDOWN_MAX_SECONDS
            );
        } else {
            $resendCount = 0;
        }

        $jobseeker->otp_code = $otpCode;
        $jobseeker->otp_expires_at = Carbon::now()->addMinutes(self::OTP_TTL_MINUTES);
        $jobseeker->otp_send_count_today = (int) $jobseeker->otp_send_count_today + 1;
        $jobseeker->otp_resend_count = $resendCount;
        $jobseeker->otp_resend_cooldown_until = $isResend
            ? Carbon::now()->addSeconds($cooldownSeconds)
            : null;
        $jobseeker->setAttribute('otp_send_count_date', $now->toDateString());
        $jobseeker->save();

        return [
            'success' => true,
            'cooldown_seconds' => $cooldownSeconds,
            'remaining_daily_sends' => max(0, self::OTP_DAILY_LIMIT - (int) $jobseeker->otp_send_count_today),
        ];
    }

    private function refreshOtpDailyCounterWindow(Jobseeker $jobseeker, Carbon $now): void
    {
        $today = $now->toDateString();
        $raw = $jobseeker->getAttribute('otp_send_count_date');
        if ($raw instanceof \DateTimeInterface) {
            $currentDate = $raw->format('Y-m-d');
        } else {
            $currentDate = $raw ? (string) $raw : null;
        }
        if ($currentDate === $today) {
            return;
        }
        $jobseeker->otp_send_count_today = 0;
        $jobseeker->otp_resend_count = 0;
        $jobseeker->otp_resend_cooldown_until = null;
        $jobseeker->setAttribute('otp_send_count_date', $today);
        $jobseeker->save();
    }

    private function secondsUntilNextDay(Carbon $now): int
    {
        return $now->copy()->endOfDay()->diffInSeconds($now) + 1;
    }

    private function purgeIfExpiredUnverified(Jobseeker $jobseeker): bool
    {
        if ($jobseeker->hasVerifiedEmail()) {
            return false;
        }
        if (!$jobseeker->created_at) {
            return false;
        }
        if ($jobseeker->created_at->addHours(self::UNVERIFIED_TTL_HOURS)->isFuture()) {
            return false;
        }
        $jobseeker->tokens()->delete();
        $jobseeker->delete();
        return true;
    }
}
