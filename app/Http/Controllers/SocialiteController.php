<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    //

    // Function: Google Login
    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleAuthentication()
    {
        $googleUser = Socialite::driver('google')->user();
        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user) {
            if ($user->google_id == null) {
                $user->google_id = $googleUser->getId();
                $user->update();
            }
            Auth::login($user);
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login')->withErrors(['google_auth_error' => 'Login Incorrecto']);
        }
    }
}
