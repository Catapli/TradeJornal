<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

/**
 * Controlador para gestionar la autenticación de usuarios,
 * incluyendo inicio de sesión estándar, con 2FA y mediante Google.
 */
class AuthController extends Controller
{
    /**
     * Inicia sesión con credenciales (email y contraseña).
     * Si el usuario tiene 2FA activado, se requiere verificación adicional.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Intentar autenticar al usuario
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales inválidas.'
            ], 401);
        }

        // Obtener el usuario con su municipio
        $user = User::with(['town:id,town'])
            ->where('email', $request->email)
            ->firstOrFail();

        // Verificar si tiene 2FA activado
        $requires2fa = $user->hasEnabledTwoFactorAuthentication();

        if (!$requires2fa) {
            // Generar token de acceso (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'accessToken' => $token, // Corregido: "accesToken" → "accessToken"
                'token_type'  => 'Bearer',
                'user'        => $user,
                'requires_2fa' => false,
            ], 200);
        }

        // Si tiene 2FA, solicitar código
        return response()->json([
            'requires_2fa' => true,
            'message'      => 'Se requiere verificación en dos pasos.'
        ], 200);
    }

    /**
     * Verifica el código de autenticación en dos pasos (2FA).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'No se encontró un usuario con 2FA activado.'
            ], 404);
        }

        // Desencriptar el secreto y verificar el código
        try {
            $google2fa = new Google2FA();
            $secret = decrypt($user->two_factor_secret);
            $isValid = $google2fa->verifyKey($secret, $request->code);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar el código 2FA.'
            ], 500);
        }

        if (!$isValid) {
            return response()->json([
                'message' => 'Código de verificación inválido.'
            ], 401);
        }

        // Generar token de acceso tras verificación exitosa
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'token_type'  => 'Bearer',
            'user'        => $user->load('town:id,town'), // Asegurar que se incluya town
        ], 200);
    }

    /**
     * Inicio de sesión mediante cuenta de Google (OAuth).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'googleID'  => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no registrado.'
            ], 401);
        }

        // Actualizar google_id si no está presente (por compatibilidad)
        if (empty($user->google_id)) {
            $user->google_id = $request->googleID;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'token_type'  => 'Bearer',
            'user'        => $user->load('town:id,town'),
        ], 200);
    }

    /**
     * Actualiza el token FCM (Firebase Cloud Messaging) del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFCM(Request $request)
    {
        $request->validate([
            'email'      => 'required|email',
            'fcm_token'  => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }

        return response()->json([
            'message' => 'Token FCM actualizado correctamente.'
        ], 200); // 200 es más apropiado que 201 para actualizaciones
    }
}
