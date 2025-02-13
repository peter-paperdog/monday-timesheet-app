<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayGroup extends Model
{
    use HasFactory;

    public $incrementing = false; // No auto-increment
    protected $keyType = 'bigint'; // Ensures id is a big integer
    public $timestamps = false; // No timestamps

    protected $fillable = ['id', 'name', 'board_id'];

    // A group belongs to a board
    public function board()
    {
        return $this->belongsTo(MondayBoard::class, 'board_id');
    }

    // A group can have multiple items
    public function items()
    {
        return $this->hasMany(MondayItem::class, 'group_id');
    }
}
