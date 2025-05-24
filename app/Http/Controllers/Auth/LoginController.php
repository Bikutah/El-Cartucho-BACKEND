<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ← importante


class LoginController extends Controller
{

    public function submit(Request $request)
    {
        $credentials = $request->only('name', 'password');
    
        if (Auth::attempt($credentials)) {
            // Si el login es exitoso
            return redirect()->intended('dashboard'); // O a donde quieras llevarlo
        }
    
        // Si el login falla
        return back()->withInput()->with('login_error', 'Credenciales inválidas 💀');
    }
    

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');
    
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/'); // o donde quieras
        }
    
        return back()->withInput()->with('login_error', 'Credenciales inválidas 💀');
    }
    

    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
