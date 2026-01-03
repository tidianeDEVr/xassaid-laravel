<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
        if (!$this->canManageAdmins()) {
            return redirect()->back()->withErrors(['error' => 'Action non autorisée.']);
        }

        $data = $request->validated();
        try {
            $newUser = User::create([
                'email' => $data['email'],
                'name' => $this->formatName($data),
                'password' => Hash::make($data['password']),
            ]);

            $adminRole = Role::where('libelle', 'ROLE_ADMIN')->first();

            if ($adminRole) {
                $newUser->roles()->syncWithoutDetaching([$adminRole->id]);
            }

            return redirect()->back()->with('success', 'L\'utilisateur a été créé !');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => 'Erreur inconnue lors de la création !']);
        }
    }

    public function updateUser(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'L\'utilisateur a été modifié !');
    }

    public function deleteUser(User $user)
    {
        if (!$this->canManageAdmins()) {
            return redirect()->back()->withErrors(['error' => 'Action non autorisée.']);
        }

        if (Auth::id() === $user->id) {
            return redirect()->back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->back()->with('success', 'L\'utilisateur a été supprimé !');
    }

    private function formatName($data)
    {
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        return ucwords(strtolower($firstname)) . ' ' . strtoupper($lastname);
    }

    private function canManageAdmins(): bool
    {
        $user = Auth::user();

        return $user && $user->email === 'cheikhtiindiaye@gmail.com';
    }
}
