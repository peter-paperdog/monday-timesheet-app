<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceProject extends Model
{
    protected $fillable = ['invoice_group_id', 'project_id', 'external_id'];

    public function group()
    {
        return $this->belongsTo(InvoiceGroup::class, 'invoice_group_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
