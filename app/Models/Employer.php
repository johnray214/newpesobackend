<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        // ── Account ───────────────────────────────────────────────────
        'company_name',
        'legal_name',
        'contact_person',
        'email',
        'password',
        'photo',

        // ── Company Info ──────────────────────────────────────────────
        'industry',
        'company_size',
        'tagline',
        'about',
        'business_type',
        'founded',
        'perks',

        // ── Address ───────────────────────────────────────────────────
        'barangay',
        'city',
        'province',
        'address_full',
        'latitude',
        'longitude',
        'map_visible',

        // ── Contact ───────────────────────────────────────────────────
        'phone',
        'tin',
        'website',

        // ── Documents ─────────────────────────────────────────────────
        'biz_permit_path',
        'bir_cert_path',

        // ── Stats ─────────────────────────────────────────────────────
        'total_hired',

        // ── Status ────────────────────────────────────────────────────
        'status',
        'verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Single $casts — merged from your original + new fields needed by the resource
    protected $casts = [
        'password'    => 'hashed',
        'latitude'    => 'decimal:8',
        'longitude'   => 'decimal:8',
        'map_visible' => 'boolean',
        'verified_at' => 'datetime',
        // perks is JSON in DB → always an array in PHP
        'perks'       => 'array',
        'founded'     => 'integer',
        'total_hired' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function jobListings()
    {
        return $this->hasMany(JobListing::class);
    }

    // Alias so EmployerResource whenLoaded('jobs') works without renaming everywhere
    public function jobs()
    {
        return $this->hasMany(JobListing::class);
    }

    public function notificationReads()
    {
        return $this->morphMany(NotificationRead::class, 'recipient');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function openJobsCount(): int
    {
        return $this->jobListings()->where('status', 'open')->count();
    }

    public function sendPasswordResetNotification($token)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:8080');
        $resetUrl = $frontendUrl . '/employer/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        $name = $this->contact_person ?: $this->company_name ?: 'Employer';

        try {
            $mj = new \Mailjet\Client(env('MAILJET_API_KEY'), env('MAILJET_SECRET_KEY'), true, ['version' => 'v3.1']);
            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => env('MAILJET_FROM_EMAIL', 'yujohnray96@gmail.com'),
                            'Name'  => env('MAILJET_FROM_NAME', 'PESO')
                        ],
                        'To' => [
                            [
                                'Email' => $this->email,
                                'Name'  => $name
                            ]
                        ],
                        'TemplateID' => 7861338,
                        'TemplateLanguage' => true,
                        'Subject' => 'Reset Your Password — PESO',
                        'Variables' => [
                            'first_name'   => $name,
                            'email'        => $this->email,
                            'reset_url'    => $resetUrl,
                            'request_time' => now()->format('F j, Y, g:i A')
                        ]
                    ]
                ]
            ];
            $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Mailjet Exception (Employer Password Reset): ' . $e->getMessage());
        }
    }
}