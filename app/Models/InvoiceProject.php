<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceProject extends Model
{
    public $timestamps = false;
    protected $fillable = ['invoice_id', 'project_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function invoiceGroups()
    {
        return $this->hasMany(InvoiceGroup::class);
    }
}
