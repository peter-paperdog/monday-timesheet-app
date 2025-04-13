<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    public function index()
    {
        $contacts = ['John Doe', 'Jane Smith', 'Alice Johnson'];
        $projects = ['Project Alpha', 'Project Beta', 'Project Gamma'];

        return view('invoicing.index', compact('contacts', 'projects'));
    }

    public function store(Request $request)
    {

        // Redirect back to the index with a success message
        return redirect()->route('invoicing.index')->with('message', 'Invoice created successfully!');
    }
}
