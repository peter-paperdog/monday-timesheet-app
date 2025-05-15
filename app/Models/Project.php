<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['monday_id', 'name'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
