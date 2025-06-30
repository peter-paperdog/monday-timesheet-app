<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['id', 'name', 'group_id', 'project_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function projects()
    {
        return $this->belongsTo(Project::class);
    }

    public function timeTrackings()
    {
        return $this->hasMany(TimeTracking::class, 'item_id');
    }
}
