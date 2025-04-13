<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    public function index()
    {
        return view('invoicing.index');
    }
}
