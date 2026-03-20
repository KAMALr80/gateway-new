<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load('orders');

        return response()->json([
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'phone'          => $user->phone,
            'address'        => $user->address,
            'erp_contact_id' => $user->erp_contact_id,
            'orders_count'   => $user->orders->count(),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $authUser = auth('api')->user();

        // Users can only update their own profile
        if ($authUser->id !== $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $authUser->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => [
                'id'      => $authUser->id,
                'name'    => $authUser->name,
                'email'   => $authUser->email,
                'phone'   => $authUser->phone,
                'address' => $authUser->address,
            ],
        ]);
    }
}