<?php

namespace App\Http\Controllers;

use App\Models\SzamlazzInvoice;
use App\Models\SzamlazzTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SzamlazzController extends Controller
{
    public function handleSzamlabe(Request $request, $key = null)
    {
        Log::info('Számla BE:', $this->logData($request));

        Log::info('Számla BE:', $this->logData($request));

        $xmlContent = $request->getContent();
        $xml = simplexml_load_string($xmlContent);
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('ns', $namespaces[''] ?? 'http://www.szamlazz.hu/szamlabe');

        // Kinyerjük az adatokat
        $id = (int) ($xml->xpath('//ns:alap/ns:id')[0] ?? 0);
        $szamlaszam = (string) ($xml->xpath('//ns:alap/ns:szamlaszam')[0] ?? '');
        $kelt = (string) ($xml->xpath('//ns:alap/ns:kelt')[0] ?? '');
        $telj = (string) ($xml->xpath('//ns:alap/ns:telj')[0] ?? '');
        $fizh = (string) ($xml->xpath('//ns:alap/ns:fizh')[0] ?? '');
        $fizmod = (string) ($xml->xpath('//ns:alap/ns:fizmod')[0] ?? '');
        $devizanem = (string) ($xml->xpath('//ns:alap/ns:devizanem')[0] ?? '');
        $teszt = (string) ($xml->xpath('//ns:alap/ns:teszt')[0] ?? 'false') === 'true';
        $sztornozott = (string) ($xml->xpath('//ns:alap/ns:sztornozott')[0] ?? 'false') === 'true';

        $netto = (float) ($xml->xpath('//ns:totalossz/ns:netto')[0] ?? 0);
        $afa = (float) ($xml->xpath('//ns:totalossz/ns:afa')[0] ?? 0);
        $brutto = (float) ($xml->xpath('//ns:totalossz/ns:brutto')[0] ?? 0);

        $vevo_nev = (string) ($xml->xpath('//ns:vevo/ns:nev')[0] ?? '');
        $vevo_adoszam = (string) ($xml->xpath('//ns:vevo/ns:adoszam')[0] ?? '');

        $szallito_nev = (string) ($xml->xpath('//ns:szallito/ns:nev')[0] ?? '');
        $szallito_adoszam = (string) ($xml->xpath('//ns:szallito/ns:adoszam')[0] ?? '');

        // Mentés
        $invoice = SzamlazzInvoice::firstOrCreate(
            ['id' => $id],
            compact(
                'szamlaszam', 'kelt', 'telj', 'fizh', 'fizmod', 'devizanem',
                'netto', 'afa', 'brutto', 'vevo_nev', 'vevo_adoszam',
                'szallito_nev', 'szallito_adoszam', 'teszt', 'sztornozott'
            )
        );

        $iktatoszam = 'IKT-' . $invoice->id;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<szamlabevalasz xmlns="http://www.szamlazz.hu/szamlabevalasz" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.szamlazz.hu/szamlabevalasz">' .
            '<alap>' .
            '<id>' . $id . '</id>' .
            '<iktatoszam>' . $iktatoszam . '</iktatoszam>' .
            '</alap>' .
            '</szamlabevalasz>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function handleSzamlaki(Request $request, $key = null)
    {
        Log::info('Számla KI:', $this->logData($request));

        $xmlContent = $request->getContent();
        $xml = simplexml_load_string($xmlContent);
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('ns', $namespaces[''] ?? 'http://www.szamlazz.hu/szamla');

        // Kinyerjük az adatokat
        $id = (int) ($xml->xpath('//ns:alap/ns:id')[0] ?? 0);
        $szamlaszam = (string) ($xml->xpath('//ns:alap/ns:szamlaszam')[0] ?? '');
        $kelt = (string) ($xml->xpath('//ns:alap/ns:kelt')[0] ?? '');
        $telj = (string) ($xml->xpath('//ns:alap/ns:telj')[0] ?? '');
        $fizh = (string) ($xml->xpath('//ns:alap/ns:fizh')[0] ?? '');
        $fizmod = (string) ($xml->xpath('//ns:alap/ns:fizmod')[0] ?? '');
        $devizanem = (string) ($xml->xpath('//ns:alap/ns:devizanem')[0] ?? '');
        $teszt = (string) ($xml->xpath('//ns:alap/ns:teszt')[0] ?? 'false') === 'true';
        $sztornozott = (string) ($xml->xpath('//ns:alap/ns:sztornozott')[0] ?? 'false') === 'true';

        $netto = (float) ($xml->xpath('//ns:totalossz/ns:netto')[0] ?? 0);
        $afa = (float) ($xml->xpath('//ns:totalossz/ns:afa')[0] ?? 0);
        $brutto = (float) ($xml->xpath('//ns:totalossz/ns:brutto')[0] ?? 0);

        $vevo_nev = (string) ($xml->xpath('//ns:vevo/ns:nev')[0] ?? '');
        $vevo_adoszam= (string) ($xml->xpath('//ns:vevo/ns:adoszam')[0] ?? '');

        $szallito_nev = (string) ($xml->xpath('//ns:szallito/ns:nev')[0] ?? '');
        $szallito_adoszam = (string) ($xml->xpath('//ns:szallito/ns:adoszam')[0] ?? '');

        $szamlaszam = '';



        // Megkeressük vagy létrehozzuk
        $invoice = SzamlazzInvoice::firstOrCreate(
            ['id' => $id],
            compact(
                'id','kelt', 'telj', 'fizh', 'fizmod', 'devizanem',
                'netto', 'afa', 'brutto',
                'vevo_nev', 'vevo_adoszam',
                'szallito_nev', 'szallito_adoszam',
                'teszt', 'sztornozott', 'szamlaszam'
            )
        );

        $iktatoszam = 'IKT-' . $invoice->id;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<szamlavalasz xmlns="http://www.szamlazz.hu/szamlavalasz" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.szamlazz.hu/szamlavalasz">' .
            '<alap>' .
            '<id>' . $id . '</id>' .
            '<iktatoszam>' . $iktatoszam . '</iktatoszam>' .
            '</alap>' .
            '</szamlavalasz>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function handleBanktranz(Request $request, $key = null)
    {
        Log::info('Banktranz:', $this->logData($request));

        Log::info('Banktranz:', $this->logData($request));

        $xml = simplexml_load_string($request->getContent());
        $xml->registerXPathNamespace('ns', 'http://www.szamlazz.hu/banktranz');

        $id = (int) ($xml->xpath('//ns:id')[0] ?? 0);
        $bankszamla = (string) ($xml->xpath('//ns:bankszamla')[0] ?? '');
        $erteknap = (string) ($xml->xpath('//ns:erteknap')[0] ?? '');
        $irany = (string) ($xml->xpath('//ns:irany')[0] ?? '');
        $tipus = (string) ($xml->xpath('//ns:tipus')[0] ?? null);
        $technikai = (string) ($xml->xpath('//ns:technikai')[0] ?? 'false') === 'true';
        $osszeg = (float) ($xml->xpath('//ns:osszeg')[0] ?? 0);
        $devizanem = (string) ($xml->xpath('//ns:devizanem')[0] ?? '');
        $partner_nev = (string) ($xml->xpath('//ns:partner/ns:nev')[0] ?? null);
        $partner_bankszamla = (string) ($xml->xpath('//ns:partner/ns:bankszamla')[0] ?? null);
        $kozlemeny = (string) ($xml->xpath('//ns:kozlemeny')[0] ?? null);

        SzamlazzTransaction::updateOrCreate(
            ['id' => $id],
            compact(
                'bankszamla', 'erteknap', 'irany', 'tipus', 'technikai',
                'osszeg', 'devizanem', 'partner_nev', 'partner_bankszamla', 'kozlemeny'
            )
        );

        $responseXml = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<banktranzvalasz xmlns="http://www.szamlazz.hu/banktranzvalasz" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.szamlazz.hu/banktranzvalasz">' .
            '</banktranzvalasz>';

        return response($responseXml, 200)->header('Content-Type', 'application/xml');
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
