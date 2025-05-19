<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'monday_id', 'description', 'type', 'quantity', 'unit',
        'unit_price', 'currency', 'project_id', 'board_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function invoices()
    {
        return $this->belongsTo(Invoice::class);
    }
}
