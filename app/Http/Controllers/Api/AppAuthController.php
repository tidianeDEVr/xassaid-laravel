<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppLoginRequest;
use App\Http\Requests\AppRegisterRequest;
use App\Http\Resources\AppUserResource;
use App\Models\AppUser;
use App\Support\MediaOptimizer;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppAuthController extends Controller
{
    public function register(AppRegisterRequest $request)
    {
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeAvatar($request->file('avatar'));
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
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $previous = $user->avatar_path;

        $user->update(['avatar_path' => $this->storeAvatar($request->file('avatar'))]);

        if ($previous) {
            MediaStorage::delete($previous);
        }

        return response()->json(['user' => new AppUserResource($user)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    private function storeAvatar(UploadedFile $file): string
    {
        // Carré 600x600 : recadrage centré, l'app l'affiche toujours en rond.
        $optimized = MediaOptimizer::squareAvatar($file, 600);
        if ($optimized === null) {
            abort(422, 'Image illisible.');
        }

        $filename = 'avatars/' . Str::uuid() . '.' . $optimized['extension'];
        MediaStorage::put($filename, file_get_contents($optimized['path']));

        if ($optimized['cleanup']) {
            @unlink($optimized['path']);
        }

        return $filename;
    }
}
