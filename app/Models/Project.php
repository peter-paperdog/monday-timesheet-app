<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['id', 'name', 'client_id', 'time_board_id', 'expenses_board_id'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
