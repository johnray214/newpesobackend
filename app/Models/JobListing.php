<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobListing extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Always expose absolute storage URL for employer logo in JSON.
     * Relying on controller-only setAttribute() can omit non-column keys from
     * serialization in some cases; an accessor + $appends is reliable.
     *
     * @var list<string>
     */
    protected $appends = [
        'employer_photo_url',
    ];

    protected $fillable = [
        'employer_id',
        'title',
        'type',
        'location',
        'salary_range',
        'education_level',
        'experience_required',
        'description',
        'slots',
        'status',
        'posted_date',
        'deadline',
    ];

    protected $casts = [
        'posted_date' => 'date',
        'deadline' => 'date',
        'slots' => 'integer',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function skills()
    {
        return $this->hasMany(JobSkill::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Full public URL for the employer's logo (public disk under /storage/...).
     */
    protected function employerPhotoUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->relationLoaded('employer') || $this->employer === null) {
                    return null;
                }
                $stored = $this->employer->photo;
                if ($stored === null || $stored === '') {
                    return null;
                }

                return PublicStorageUrl::fromRequest(request(), $stored);
            }
        );
    }
}
