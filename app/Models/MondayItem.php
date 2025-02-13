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
    protected $fillable = ['id', 'board_id', 'name'];
    protected $casts = [
        'id' => 'integer',
        'board_id' => 'integer'
    ];
}
