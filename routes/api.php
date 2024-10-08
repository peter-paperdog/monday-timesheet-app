<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function (Request $request) {
    $startOfWeek = new DateTime();
    $startOfWeek->modify('Monday last week');

    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('Sunday last week');

    var_dump($startOfWeek);
    var_dump($endOfWeek);
    die();
});
