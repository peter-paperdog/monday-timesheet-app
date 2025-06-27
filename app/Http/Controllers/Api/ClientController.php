<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * @return AnonymousResourceCollection
     */
    public function index()
    {
        return ClientResource::collection(Client::all());
    }

    /**
     * @param $id
     * @return ClientResource
     */
    public function show($id)
    {
        $client = Client::findOrFail($id);
        return new ClientResource($client);
    }
}
