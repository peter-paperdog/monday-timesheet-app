<?php

namespace App\Http\Controllers;

use App\Services\MondayService;
use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    public function __construct(private MondayService $mondayService){

    }

    public function create()
    {
        return view('invoicing.create');
    }
    public function index()
    {
        $data = $this->mondayService->getFolders();

        $clients = $data->clients;
        $projects = $data->projects;
        $folders = $data->folders;

        return view('invoicing.index', compact('clients', 'projects', 'folders'));
    }

    public function store(Request $request)
    {

        // Redirect back to the index with a success message
        return redirect()->route('invoicing.index')->with('success', 'Invoice created successfully!');
    }
}
