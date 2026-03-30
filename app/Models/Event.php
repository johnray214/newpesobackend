<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'location',
        'event_date',
        'start_time',
        'end_time',
        'organizer',
        'max_participants',
        'status',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'max_participants' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function getStatusAttribute($value)
    {
        // Don't override cancelled status
        if ($value === 'cancelled') {
            return $value;
        }

        if (!$this->event_date || !$this->start_time) {
            return $value; // Fallback
        }

        try {
            $tz = 'Asia/Manila';
            $now = now()->setTimezone($tz);
            
            // event_date is cast to 'date', so it is a Carbon instance
            $dateStr = $this->event_date instanceof \Carbon\Carbon 
                ? $this->event_date->format('Y-m-d') 
                : $this->event_date;

            $start = \Carbon\Carbon::parse($dateStr . ' ' . $this->start_time, $tz);
            $end = $this->end_time 
                ? \Carbon\Carbon::parse($dateStr . ' ' . $this->end_time, $tz) 
                : null;

            if ($now < $start) {
                return 'upcoming';
            }
            if ($end && $now > $end) {
                return 'completed';
            }
            // If there's no end time, assume it's completed after 6 hours from start
            if (!$end && $now > clone($start)->addHours(6)) {
                return 'completed';
            }
            
            return 'ongoing';
        } catch (\Exception $e) {
            return $value;
        }
    }
}
