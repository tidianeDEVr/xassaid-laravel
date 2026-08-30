<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppLoginRequest;
use App\Http\Requests\AppRegisterRequest;
use App\Http\Resources\AppUserResource;
use App\Models\AppUser;
use App\Support\AvatarFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AppAuthController extends Controller
{
    public function register(AppRegisterRequest $request)
    {
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = AvatarFile::store($request->file('avatar'));
        }

        $user = AppUser::create([
            'username' => $request->username,
            'display_name' => $request->display_name,
            'password' => $request->password,
            'avatar_path' => $avatarPath,
        ]);

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => new AppUserResource($user),
        ], 201);
    }

    public function login(AppLoginRequest $request)
    {
        $user = AppUser::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Pseudo ou mot de passe incorrect.',
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => new AppUserResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => new AppUserResource($request->user()),
        ]);
    }

    /**
     * POST /v2/me/avatar — remplace l'avatar du compte connecté.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => array_merge(['required'], AvatarFile::RULES),
        ]);

        $user = $request->user();
        $user->update([
            'avatar_path' => AvatarFile::replace($request->file('avatar'), $user->avatar_path),
        ]);

        return response()->json(['user' => new AppUserResource($user)]);
    }

    /**
     * POST /v2/auth/password — change le mot de passe du compte connecté.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            // Mêmes bornes qu'à l'inscription (AppRegisterRequest).
            'password' => ['required', 'string', 'min:6', 'max:72'],
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Mot de passe modifié.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

}
