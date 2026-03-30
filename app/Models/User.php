<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'role',
        'sex',
        'contact',
        'address',
        'status',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function events()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    public function fullName(): string
    {
        return $this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name;
    }

    public function sendPasswordResetNotification($token)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:8080');
        $resetUrl = $frontendUrl . '/admin/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        $name = $this->first_name . ' ' . $this->last_name;

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
            \Illuminate\Support\Facades\Log::error('Mailjet Exception (Admin Password Reset): ' . $e->getMessage());
        }
    }
}
