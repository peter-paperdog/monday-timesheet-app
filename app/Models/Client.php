<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['id', 'name'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
