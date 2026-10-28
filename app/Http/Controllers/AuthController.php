<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Staff log in with email; patients log in with their phone number + access code. */
    public function login(Request $request)
    {
        $request->validate(['login' => ['required', 'string'], 'password' => ['required']]);

        $id = trim($request->input('login'));
        $isEmail = str_contains($id, '@');

        $credentials = [
            $isEmail ? 'email' : 'username' => $isEmail ? $id : preg_replace('/[^0-9\-]/', '', $id),
            'password' => $request->input('password'),
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route(Auth::user()->homeRoute()));
        }

        return back()->withErrors(['login' => 'We could not find an active account with those details.'])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
