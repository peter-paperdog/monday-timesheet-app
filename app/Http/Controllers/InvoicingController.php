<?php

namespace App\Http\Controllers;

use App\Services\MondayService;
use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    public function __construct(private MondayService $mondayService)
    {

    }

    public function create(Request $request)
    {
        $clientId = $request->input('client');
        $projectId = $request->input('project');
        $boardIds = explode(',', $request->input('folders', ''));

        $clientName = $this->mondayService->getFoldername($clientId);

        if (!empty($projectId) && $projectId !== '-1') {
            $projectName = $this->mondayService->getFoldername($projectId);
        } else {
            $projectName = null;
        }

        $boards = [];

        foreach ($boardIds as $boardId) {
            $boards[] = $this->mondayService->getBoard($boardId);
        }

        return view('invoicing.create', compact('clientName', 'projectName', 'boards'));
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

    public function init()
    {
        $mondayService = new MondayService();
        $data = $mondayService->getFolders();
        $clients = $data->clients;
        $projects = $data->projects;
        $folders = $data->folders;

        return response()->json([
            "clients" => $data->clients,
            "projects" => $data->projects,
            "boards" => $data->folders
        ]);
    }
}
