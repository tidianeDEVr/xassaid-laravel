<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    public function login(){
        return view('security.login');
    }

    public function doLogin(LoginRequest $request){
        $credentials = $request->validated();
        
        if(Auth::attempt($credentials)){
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return to_route('security.login')->withErrors(['error'=>'Veuillez réessayer !'])->onlyInput('email');
    }

    public function logout(){
        Auth::logout();
        return to_route('security.login');
    }
}
