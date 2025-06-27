<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Client;
use App\Models\Contact;

class ContactController extends Controller
{
    public function index()
    {
        return ContactResource::collection(Contact::all());
    }

    public function indexByClient(Client $client)
    {
        return ContactResource::collection($client->contacts);
    }

    public function show(Contact $client)
    {
        return new ContactResource($client);
    }
}
