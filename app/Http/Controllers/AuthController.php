<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'You have been logged out',
        ]);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        assert($request->user() !== null);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'You have been logged out',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'filled', 'string'],
            'email' => ['required', 'email', 'ends_with:uoc.gr', 'unique:App\Models\User'],
            'password' => ['required', 'string', 'confirmed', new Password],
            'stateless' => ['sometimes', 'nullable', 'string'],
        ]);
        assert(is_array($validated));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (Arr::has($validated, 'stateless')) {
            $tokenName = $validated['stateless'] ?? 'token'.now()->timestamp;
            assert(is_string($tokenName));

            $token = $user->createToken($tokenName);

            return response()->json([
                'message' => 'Your account has been created',
                'user' => $user,
                'token' => $token,
            ]);
        }

        // we are statefull here

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Your account has been created',
            'user' => $user,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'ends_with:uoc.gr'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::ResetLinkSent) {
            return response()->json([
                'message' => __($status),
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => ['required', 'email', 'ends_with:uoc.gr'],
            'password' => ['required', 'min:12', 'confirmed', new Password],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return response()->json([
                'message' => __($status),
            ]);
        }

        assert(is_string($status));

        return response()->json([
            'message' => __($status),
        ], 422);
    }
}
