<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => $request->password,
            'approval_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registration successful. Your account is pending approval before you can log in.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = auth('api')->attempt($request->only('email', 'password'));

        if (!$token) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();

        if ($user->approval_status !== 'approved') {
            auth('api')->logout();
            return response()->json(['message' => 'Your account is pending approval.'], 403);
        }

        return $this->tokenResponse($token);
    }

    public function validate(): JsonResponse
    {
        return response()->json([
            'valid' => true,
            'user'  => $this->formatUser(auth('api')->user()),
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            return $this->tokenResponse($token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token cannot be refreshed'], 401);
        }
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }

    private function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => $this->formatUser(auth('api')->user()),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'address'        => $user->address,
            'erp_contact_id' => $user->erp_contact_id,
        ];
    }
}
