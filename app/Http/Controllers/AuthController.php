<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return $this->tokenResponse($token);
    }

    public function validate(): JsonResponse
    {
        // If request reaches here, middleware already validated the token
        return response()->json(['valid' => true, 'user' => auth('api')->user()]);
    }

    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();
        return $this->tokenResponse($token);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logged out']);
    }

    private function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => auth('api')->user(),
        ]);
    }
}