<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function __construct(private CaptchaService $captchaService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        if (!$this->captchaService->validate($request->captcha_token, $request->captcha_answer)) {
            return response()->json(['error' => 'Invalid captcha'], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $executed = RateLimiter::attempt(
            'login:' . $request->ip(),
            $perMinute = 5,
            function() {}
        );

        if (!$executed) {
            return response()->json(['error' => 'Too many login attempts. Please try again later.'], 429);
        }

        if (!$this->captchaService->validate($request->captcha_token, $request->captcha_answer)) {
            return response()->json(['error' => 'Invalid captcha'], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit('login:' . $request->ip());
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        RateLimiter::clear('login:' . $request->ip());

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    public function captcha(): JsonResponse
    {
        $captcha = $this->captchaService->generate();
        return response()->json([
            'question' => $captcha['question'],
            'token' => $captcha['token'],
        ]);
    }
}