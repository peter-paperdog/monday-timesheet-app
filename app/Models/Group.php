<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'project_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    public function updateDurationSummary(): void
    {
        $total = $this->tasks()->sum('duration_seconds');
        $this->duration_seconds = $total;
        $this->save();

        $this->project?->updateDurationSummary();
    }
}
