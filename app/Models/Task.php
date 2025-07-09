<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Task extends Model
{
    protected $fillable = ['id', 'name', 'group_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function timeTrackings()
    {
        return $this->hasMany(MondayTimeTracking::class, 'item_id', 'id');
    }

    public function taskable()
    {
        return $this->morphTo();
    }
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_task');
    }
    public function updateDurationSummary(): void
    {
        $total = $this->timeTrackings()->sum(DB::raw('TIMESTAMPDIFF(SECOND, started_at, ended_at)'));
        $this->duration_seconds = $total;
        $this->save();

        Log::info("Duration summary updated for task {$this->name} (ID: {$this->id}): {$total} seconds");

        // Update group and project too
        $this->group?->updateDurationSummary();
    }
}
