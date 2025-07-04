<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceGroup extends Model
{
    protected $fillable = ['invoice_id', 'name'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceProject()
    {
        return $this->hasOne(InvoiceProject::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
