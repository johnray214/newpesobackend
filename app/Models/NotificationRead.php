<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($notificationRead) {
            // Automatically send Push Notification if the recipient is a jobseeker
            if ($notificationRead->recipient_type === 'jobseeker') {
                $jobseeker = \App\Models\Jobseeker::find($notificationRead->recipient_id);
                
                if ($jobseeker && $jobseeker->fcm_token) {
                    $notification = $notificationRead->notification;
                    
                    \App\Services\FcmService::sendNotification(
                        $jobseeker->id, // Passing ID instead of token
                        $notification->subject,
                        $notification->message,
                        [
                            'notification_id' => (string) $notification->id,
                            'type' => (string) $notification->type,
                        ]
                    );
                }
            }
        });
    }

    protected $fillable = [
        'notification_id',
        'recipient_type',
        'recipient_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function recipient()
    {
        return $this->morphTo(__FUNCTION__, 'recipient_type', 'recipient_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}
