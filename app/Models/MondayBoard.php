<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayBoard extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'name', 'type', 'activity_at'];

    protected $casts = [
        'id' => 'integer',
        'activity_at' => 'datetime', // Ensure it is treated as a timestamp
    ];

    /**
     * A board has many items.
     */
    public function items()
    {
        return $this->hasMany(MondayItem::class, 'board_id', 'id');
    }
}
