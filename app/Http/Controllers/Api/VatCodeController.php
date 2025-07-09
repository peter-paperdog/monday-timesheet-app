<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VatCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $vatCodes = [
            [
                'code' => 'T1',
                'description' => 'Standard-rated supply',
                'rate' => '20%',
                'numeric_rate' => 0.20,
            ],
            [
                'code' => 'T2',
                'description' => 'Zero-rated supply',
                'rate' => '0%',
                'numeric_rate' => 0.00,
            ],
            [
                'code' => 'T3',
                'description' => 'Exempt supply',
                'rate' => '0%',
                'numeric_rate' => 0.00,
            ],
            [
                'code' => 'T4',
                'description' => 'Outside the scope of VAT',
                'rate' => 'N/A',
                'numeric_rate' => null,
            ],
        ];

        return response()->json($vatCodes);
    }
}
