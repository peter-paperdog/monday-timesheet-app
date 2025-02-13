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

        $user = User::where('email', $socialiteUser->getEmail())->firstOrFail();


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

        return redirect(route('dashboard'));
    }
}
