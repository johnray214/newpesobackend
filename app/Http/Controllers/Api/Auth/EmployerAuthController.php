<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerResource;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Support\Facades\Log;

class EmployerAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|unique:employers|max:191',
            'password'       => 'required|string|min:8|confirmed',
            'industry'       => 'required|string|max:100',
            'company_size'   => 'required|string|max:30',
            'province'       => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'barangay'       => 'required|string|max:100',
            'address_full'   => 'nullable|string|max:255',
            'phone'          => 'required|string|max:20',
            'tin'            => 'nullable|string|max:50',
            'website'        => 'nullable|string|max:255',
            'biz_permit'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'bir_cert'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status']   = 'pending';

        // Build full address from parts
        $parts = array_filter([
            $validated['barangay'] ?? null,
            $validated['city']     ?? null,
            $validated['province'] ?? null,
        ]);
        if (!isset($validated['address_full']) && count($parts)) {
            $validated['address_full'] = implode(', ', $parts);
        }

        $employer = Employer::create($validated);

        // Handle file uploads
        foreach (['biz_permit' => 'biz_permit_path', 'bir_cert' => 'bir_cert_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store(
                    "employer-docs/{$employer->id}",
                    'public'
                );
                $employer->update([$column => $path]);
            }
        }

        // CREATE WELCOME NOTIFICATION
        try {
            $notif = Notification::create([
                'subject' => "Welcome to PESO Connect!, {$employer->company_name}! 🏢",
                'message' => "We're glad to have you on board! PESO Connect! is here to help you find the best talent. Your account is currently pending verification by our PESO staff. This standard process ensures a secure platform for everyone. We'll notify you as soon as your account is approved. In the meantime, feel free to explore our platform features!",
                'type' => 'welcome',
                'recipients' => 'specific',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            NotificationRead::create([
                'notification_id' => $notif->id,
                'recipient_type' => 'employer',
                'recipient_id' => $employer->id,
                'read_at' => null,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create welcome notification for employer {$employer->id}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data'    => ['employer' => new EmployerResource($employer)],
            'message' => 'Registration successful. Your account is pending verification.',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $employer = Employer::where('email', $request->email)->first();

        if (!$employer || !Hash::check($request->password, $employer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($employer->status !== 'verified') {
            $messages = [
                'pending'   => 'Your account is Pending Verification. Please wait for PESO staff to approve your account.',
                'rejected'  => 'Your account registration has been Rejected. Please contact PESO for more information.',
                'suspended' => 'Your account has been Suspended. Please contact PESO for more information.',
            ];

            return response()->json([
                'success' => false,
                'message' => $messages[$employer->status] ?? 'Your account is not verified yet.',
                'status'  => $employer->status,
            ], 403);
        }

        $token = $employer->createToken('employer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'employer' => new EmployerResource($employer),
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
            'data' => new EmployerResource($request->user()),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:employers,email']);

        $status = \Illuminate\Support\Facades\Password::broker('employers')->sendResetLink($request->only('email'));

        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return response()->json(['success' => true, 'message' => 'Password reset link sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send reset link.'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = \Illuminate\Support\Facades\Password::broker('employers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password)
                ])->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json(['success' => true, 'message' => 'Password has been securely reset.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to reset password, link might be invalid or expired.'], 400);
    }
}
