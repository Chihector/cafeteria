<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Redirige al módulo principal según el rol (o al login si es invitado).
     */
    public function home()
    {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return redirect(auth()->user()->homeRoute());
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            Auditoria::registrar(
                'login',
                'auth',
                'Inicio de sesión de '.auth()->user()->email,
                auth()->user(),
                ['rol' => auth()->user()->role],
            );

            // Cada rol aterriza en su módulo principal.
            return redirect(auth()->user()->homeRoute());
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            Auditoria::registrar(
                'logout',
                'auth',
                'Cierre de sesión de '.auth()->user()->email,
                auth()->user(),
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
