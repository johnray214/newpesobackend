<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Jobseeker extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_initial',
        'last_name',
        'email',
        'email_verified_at',
        'otp_code',
        'otp_expires_at',
        'otp_send_count_today',
        'otp_send_count_date',
        'otp_resend_count',
        'otp_resend_cooldown_until',
        'password',
        'contact',
        'address',
        'province_code',
        'province_name',
        'city_code',
        'city_name',
        'barangay_code',
        'barangay_name',
        'street_address',
        'sex',
        'date_of_birth',
        'bio',
        'resume_path',
        'education_level',
        'job_experience',
        'certificate_path',
        'barangay_clearance_path',
        'avatar_path',
        'latitude',
        'longitude',
        'is_onboarding_done',
        'has_received_satisfaction_survey',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['full_name'];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_resend_cooldown_until' => 'datetime',
        'is_onboarding_done' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function getMiddleInitialAttribute($value)
    {
        if (is_string($value) && strlen($value) == 1) {
            return $value . '.';
        }
        return $value;
    }

    public function setMiddleInitialAttribute($value)
    {
        // Store only the character if it has a dot
        if (is_string($value) && strlen($value) == 2 && substr($value, -1) === '.') {
            $this->attributes['middle_initial'] = substr($value, 0, 1);
        } else {
            $this->attributes['middle_initial'] = $value;
        }
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_initial ? $this->middle_initial . '. ' : '') . $this->last_name);
    }

    public function skills()
    {
        return $this->hasMany(JobseekerSkill::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function notificationReads()
    {
        return $this->morphMany(NotificationRead::class, 'recipient');
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_initial ? $this->middle_initial . '. ' : '') . $this->last_name);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function sendPasswordResetNotification($token)
    {
        $base = rtrim((string) config('app.url'), '/');
        $resetUrl = $base.'/jobseeker/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $this->email,
        ]);
        $name = $this->first_name;

        try {
            $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => env('MAILJET_FROM_EMAIL', 'yujohnray96@gmail.com'),
                            'Name'  => env('MAILJET_FROM_NAME', 'PESO Connect')
                        ],
                        'To' => [
                            [
                                'Email' => $this->email,
                                'Name'  => $name
                            ]
                        ],
                        'TemplateID' => 7861338,
                        'TemplateLanguage' => true,
                        'Subject' => 'Reset Your Password — PESO Connect',
                        'Variables' => [
                            'first_name'   => $name,
                            'email'        => $this->email,
                            'reset_url'    => $resetUrl,
                            'request_time' => now()->format('F j, Y, g:i A')
                        ]
                    ]
                ]
            ];
            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Mailjet transport (Jobseeker Password Reset): ' . $e->getMessage());
            throw $e;
        }

        if (! $response->success()) {
            \Illuminate\Support\Facades\Log::error(
                'Mailjet API Error (Jobseeker Password Reset): ' . json_encode($response->getData())
            );
            throw new \RuntimeException('Failed to send password reset email.');
        }
    }
}
