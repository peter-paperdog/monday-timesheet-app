<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'invoice_group_id',
        'description',
        'qty',
        'price',
        'project_id',
        'task_id',
        'TAX',
        'discount'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
    public function group()
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id');
    }
}
