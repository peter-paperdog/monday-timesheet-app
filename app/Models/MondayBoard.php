<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayBoard extends Model
{
    use HasFactory;
    public $incrementing = false; // Disable auto-incrementing
    protected $keyType = 'string'; // Ensure it's treated as a string

    protected $fillable = ['id', 'name', 'type'];

}
