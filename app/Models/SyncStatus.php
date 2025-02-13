<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncStatus extends Model
{
    use HasFactory;

    public $timestamps = false; // No created_at or updated_at needed
    protected $fillable = ['type', 'last_synced_at'];

    public static function recordSync($type)
    {
        self::updateOrCreate(
            ['type' => $type],
            ['last_synced_at' => now()]
        );
    }
}
