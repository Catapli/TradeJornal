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
        // El redirect_uri se calcula a partir de APP_URL en vez de leerse de
        // GOOGLE_CALLBACK_REDIRECTS: con dos fuentes podían desincronizarse (el .env
        // traía "http://localhost:8000/auth/google-callback" mientras APP_URL apuntaba
        // a otra cosa), y Google exige que la URI enviada sea EXACTAMENTE una de las
        // registradas en el proyecto de Google Cloud, o responde 400 redirect_uri_mismatch.
        return Socialite::driver('google')
            ->redirectUrl(route('auth.google-callback'))
            ->redirect();
    }

    public function googleAuthentication()
    {
        // El intercambio del código por el token también manda el redirect_uri, y Google
        // exige que coincida con el que se usó al pedir el permiso: misma URL que arriba.
        $googleUser = Socialite::driver('google')
            ->redirectUrl(route('auth.google-callback'))
            ->user();
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
