<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayBoard extends Model
{
    use HasFactory;
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'int';

    protected $fillable = ['id', 'name', 'type', 'updated_at'];

    protected $casts = [
        'id' => 'integer',
        'updated_at' => 'datetime'
    ];

    /**
     * A board has many items.
     */
    public function items()
    {
        return $this->hasMany(MondayItem::class, 'board_id', 'id');
    }
}
