<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBoard extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name',
        'user_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }
}
