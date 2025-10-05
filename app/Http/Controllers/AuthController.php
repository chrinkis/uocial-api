<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

enum SessionType
{
    case Statefull;
    case Stateless;
}

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        assert(is_array($credentials));

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Credentials',
            ], 401);
        }

        $user = User::firstWhere('email', $credentials['email']);
        assert($user !== null);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Welcome, '.$user->name,
            'user' => $user,
        ]);
    }

    public function getToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'token_name' => ['sometimes', 'string'],
        ]);
        assert(is_array($validated));

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Credentials',
            ], 401);
        }

        $user = Auth::user();
        assert($user !== null);

        $tokenName = $validated['token_name'] ?? 'token'.now()->timestamp;
        assert(is_string($tokenName));

        $token = $user->createToken($tokenName);

        return response()->json([
            'message' => 'Welcome, '.$user->name,
            'user' => $user,
            'token' => $token,
        ]);
    }
}
