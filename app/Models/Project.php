<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['id', 'name', 'client_id'];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
