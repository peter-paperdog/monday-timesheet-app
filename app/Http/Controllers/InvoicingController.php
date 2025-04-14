<?php

namespace App\Http\Controllers;

use App\Services\MondayService;
use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    public function __construct(private MondayService $mondayService){

    }

    public function index()
    {
        $folders = $this->mondayService->getFolders();

        $contacts = ['John Doe', 'Jane Smith', 'Alice Johnson'];
        $clients = $folders->clients;
        $projects = ['Project Alpha', 'Project Beta', 'Project Gamma'];

        return view('invoicing.index', compact('contacts','clients', 'projects'));
    }

    public function store(Request $request)
    {

        // Redirect back to the index with a success message
        return redirect()->route('invoicing.index')->with('success', 'Invoice created successfully!');
    }
}
