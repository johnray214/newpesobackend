<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'type',
        'job_listing_id',
        'recipients',
        'scheduled_at',
        'sent_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }
}
