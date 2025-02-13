<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MondayAssignment extends Model
{
    use HasFactory;

    public $incrementing = false; // No primary key
    public $timestamps = false; // No timestamps
    protected $fillable = ['item_id', 'user_id']; // Mass assignable fields
}
