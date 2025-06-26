<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardWebhook extends Model
{
    protected $table = 'monday_board_webhooks';
    protected $fillable = [
        'board_id',
        'event',
        'webhook_id',
    ];

    public $timestamps = true;
}
