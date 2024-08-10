<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function login()
    {
        // User::create([
        //     'email' => 'cheikhtiindiaye@gmail.com',
        //     'name' => 'Cheikh Tidiane NDIAYE',
        //     'password' => Hash::make('Nbjuj@7391'),
        // ]);
        // Role::create([
        //     'libelle' => 'ROLE_ADMIN'
        // ]);
        return view('security.login');
    }

    public function doLogin(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return to_route('security.login')->withErrors(['error' => 'Veuillez réessayer !'])->onlyInput('email');
    }

    public function logout()
    {
        Auth::logout();
        return to_route('security.login');
    }
}
