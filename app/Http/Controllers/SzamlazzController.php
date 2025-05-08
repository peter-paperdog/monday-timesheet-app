<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SzamlazzController extends Controller
{
    public function handleSzamlabe(Request $request, $key = null)
    {
        Log::info('Számla BE:', $this->logData($request));

        $id = random_int(1000, 9999);
        $iktatoszam = 'IKT-' . now()->format('Ymd') . '-' . $id;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<szamlabevalasz xmlns="http://www.szamlazz.hu/szamlabevalasz" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.szamlazz.hu/szamlabevalasz https://www.szamlazz.hu/szamla/docs/xsds/szamlabe/szamlabevalasz.xsd">' .
            "<alap><id>$id</id><iktatoszam>$iktatoszam</iktatoszam></alap>" .
            '</szamlabevalasz>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function handleSzamlaki(Request $request, $key = null)
    {
        Log::info('Számla KI:', $this->logData($request));

        // (Opcionálisan: itt XML-ből kiolvashatod az <id>-t)
        $id = 12345678; // vagy extractáld a request bodyból

        $body = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<szamlavalasz xmlns="http://www.szamlazz.hu/szamlavalasz" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.szamlazz.hu/szamlavalasz https://www.szamlazz.hu/szamla/docs/xsds/szamla/szamlavalasz.xsd">' .
            '<alap>' .
            '<id>' . $id . '</id>' .
            '<iktatoszam>IKT-20250508-' . $id . '</iktatoszam>' .
            '</alap>' .
            '</szamlavalasz>';

        return response($body, 200)->header('Content-Type', 'application/xml');
    }

    public function handleBanktranz(Request $request, $key = null)
    {
        Log::info('Banktranz:', $this->logData($request));

        return response('<?xml version="1.0" encoding="UTF-8"?>' .
            '<banktranzvalasz xmlns="http://www.szamlazz.hu/banktranzvalasz" />', 200)
            ->header('Content-Type', 'application/xml');
    }

    private function logData(Request $request): array
    {
        return [
            'timestamp' => now()->toDateTimeString(),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
        ];
    }
}
