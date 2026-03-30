<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'date_from',
        'date_to',
        'group_by',
        'columns',
        'export_format',
        'generated_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'columns' => 'array',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
