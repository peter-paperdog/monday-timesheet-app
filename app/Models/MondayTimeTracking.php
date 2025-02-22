<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MondayTimeTracking extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'item_id',
        'user_id',
        'started_at',
        'ended_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'id' => 'integer',
        'item_id' => 'integer',
        'user_id' => 'integer'
    ];


    /**
     * A time tracking entry belongs to an item.
     */
    public function item()
    {
        return $this->belongsTo(MondayItem::class, 'item_id', 'id');
    }

    /**
     * A time tracking entry belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

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
