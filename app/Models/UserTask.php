<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTask extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name',
        'group_id',
        'user_board_id',
    ];

    protected $casts = [
        'user_board_id' => 'integer',
    ];

    /**
     * Each user task belongs to a user board.
     */
    public function userBoard(): BelongsTo
    {
        return $this->belongsTo(UserBoard::class);
    }

    /**
     * Each task can have many time tracking entries (optional if needed).
     */
    public function timeTrackings()
    {
        return $this->morphMany(MondayTimeTracking::class, 'trackable');
    }
}
