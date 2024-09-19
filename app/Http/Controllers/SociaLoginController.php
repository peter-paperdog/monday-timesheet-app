<?php

namespace App\Http\Controllers;

use App\Models\SociaLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SociaLoginController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback()
    {
        $socialiteUser = Socialite::driver('google')->user();

        $user = User::firstOrCreate(
            [
                'email' => $socialiteUser->getEmail(),
            ],
            [
                'name' => $socialiteUser->getName(),
            ],
            [
                'password' => Hash::make(str()->random()),
            ]
        );

        SociaLogin::firstOrCreate(
            [
                'user_id' => $user->id,
                'provider' => 'google',
            ],
            [
                'provider_id' => $socialiteUser->getId()
            ]
        );

        auth()->login($user);

        return redirect(route('sheets.search'));
    }
}
