<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function renderUsers()
    {
        $users = User::all();
        return view('pages.users', ['users' => $users]);
    }

    public function createAdmin(UserRequest $request)
    {
        $data = $request->validated();
        try {
            $newUser = User::create([
                'email' => $data['email'],
                'name' => $this->formatName($data),
                'password' => Hash::make($data['password']),
            ]);

            $adminRole = Role::where('libelle', 'ROLE_ADMIN')->first();

            $newUser->roles->add($adminRole);

            return redirect()->back()->with('success', 'L\'utilisateur a été créer !');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => 'Erreur inconnue lors de la création !']);
        }
    }

    private function formatName($data)
    {
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        return ucwords(strtolower($firstname)) . ' ' . strtoupper($lastname);
    }
}
