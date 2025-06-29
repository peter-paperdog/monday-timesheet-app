<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['id', 'name', 'group_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
