<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SzamlazzTransaction extends Model
{
    protected $fillable = [
        'external_id', 'bankszamla', 'erteknap', 'irany', 'tipus',
        'technikai', 'osszeg', 'devizanem', 'partner_nev',
        'partner_bankszamla', 'kozlemeny',
    ];
}
