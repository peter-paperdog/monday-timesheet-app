<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceGroup extends Model
{
    public $timestamps = false;
    protected $fillable = ['invoice_project_id', 'name'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceProject()
    {
        return $this->belongsTo(InvoiceProject::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
