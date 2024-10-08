<?php

use App\Mail\allUserSummaryEmail;
use App\Mail\attentionEmail;
use App\Services\MondayService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
