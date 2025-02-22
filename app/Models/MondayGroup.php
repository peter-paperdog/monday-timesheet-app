<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayGroup extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // No timestamps

    protected $fillable = [
        'id',
        'name',
        'board_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string', // Cast group_id as string
        'board_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
