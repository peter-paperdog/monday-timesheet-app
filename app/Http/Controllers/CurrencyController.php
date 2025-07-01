<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(): JsonResponse
    {
        $currencies = [
            ['code' => 'GBP', 'name' => 'British Pound'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'USD', 'name' => 'US Dollar'],
            ['code' => 'HUF', 'name' => 'Hungarian Forint']
        ];

        return response()->json($currencies);
    }
}
