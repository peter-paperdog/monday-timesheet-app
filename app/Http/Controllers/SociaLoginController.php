<?php

namespace App\Http\Controllers;

use App\Models\SociaLogin;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SociaLoginController extends Controller
{
    public function redirectToProvider()
    {
        Log::info('Redirecting user to Google OAuth provider.');
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth Callback
     */
    public function handleProviderCallback()
    {
        try {
            Log::info('Google OAuth callback received.');

            // Retrieve user from Google
            $socialiteUser = Socialite::driver('google')->user();
            Log::info("Retrieved user from Google: {$socialiteUser->getEmail()}");

            // Find user by email
            $user = User::where('email', $socialiteUser->getEmail())->first();

            if (!$user) {
                Log::warning("Login attempt failed. No user found with email: {$socialiteUser->getEmail()}");
                return redirect('/')->with('error', 'No account found. Please contact an administrator.');
            }

            // Log the user ID found
            Log::info("User found in the system: {$user->name} ({$user->email})");

            // Store social login details
            SociaLogin::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google',
                ],
                [
                    'provider_id' => $socialiteUser->getId(),
                ]
            );

            // Authenticate user
            auth()->login($user);
            $user->update(['last_login_at' => now()]);
            Log::info("User successfully logged in: {$user->name} ({$user->email})");

            return redirect(route('timesheets'))->with('success', 'Login successful.');

        } catch (Exception $e) {
            Log::error('Error during Google login: ' . $e->getMessage());
            return redirect('/')->with('error', 'Login failed. Please try again later.');
        }
    }
}
