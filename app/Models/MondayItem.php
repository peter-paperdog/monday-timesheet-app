<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayItem extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $fillable = ['id', 'board_id', 'parent_id', 'name'];
    protected $casts = [
        'id' => 'integer',
        'board_id' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * An item belongs to a board.
     */
    public function board()
    {
        return $this->belongsTo(MondayBoard::class, 'board_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(MondayItem::class, 'parent_id');
    }

    // An item belongs to a group
    public function group()
    {
        return $this->belongsTo(MondayGroup::class, 'group_id');
    }

    /**
     * An item has many time tracking entries.
     */
    public function timeTrackings()
    {
        return $this->hasMany(MondayTimeTracking::class, 'item_id', 'id');
    }
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'monday_assignments', 'item_id', 'user_id');
    }
}
