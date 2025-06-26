<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardWebhook extends Model
{
    protected $fillable = ['board_id', 'webhook_id'];
}
