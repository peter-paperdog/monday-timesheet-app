<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\SociaLogin;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $idToken = $data['id_token'] ?? null;
        var_dump($idToken);
        die();

        // 1. Google token ellenőrzése
        $client = new Client(['client_id' => env('GOOGLE_CLIENT_ID_ANGULAR')]);
        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // 2. Adatok kiszedése
        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? 'Google User';

        // 3. SocialLogin keresése
        $social = SociaLogin::where('provider', 'google')
            ->where('provider_id', $googleId)
            ->first();

        if ($social) {
            $user = $social->user;
        } else {
            $user = User::first(
                ['email' => $email],
            );

            SociaLogin::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_id' => $googleId,
            ]);
        }

        if (!$user->admin) {
            return response()->json(['error' => 'Unauthorized (admin only).'], 403);
        }

        $token = $user->createToken('google-login')->plainTextToken;

        $userData = new \stdClass();
        $userData->name = $user->name;

        return response()->json([
            'token' => $token,
            'user' => $userData,
        ]);
    }
}
