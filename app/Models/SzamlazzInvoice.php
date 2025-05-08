<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SzamlazzInvoice extends Model
{
    protected $fillable = [
        'id', 'szamlaszam', 'kelt', 'telj', 'fizh', 'fizmod', 'devizanem',
        'netto', 'afa', 'brutto', 'vevo_nev', 'vevo_adoszam',
        'szallito_nev', 'szallito_adoszam', 'teszt', 'sztornozott',
    ];
}
