<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MondayTimeTracking extends Model
{
    use HasFactory;
    public $incrementing = false; // Disable auto-incrementing

    protected $fillable = [
        'id',
        'user_id',
        'started_at',
        'ended_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];


    /**
     * Format the started_at timestamp.
     */
    public function getFormattedStartedAtAttribute()
    {
        return $this->started_at ? Carbon::parse($this->started_at)->toDateTimeString() : null;
    }

    /**
     * Format the ended_at timestamp.
     */
    public function getFormattedEndedAtAttribute()
    {
        return $this->ended_at ? Carbon::parse($this->ended_at)->toDateTimeString() : null;
    }
}
