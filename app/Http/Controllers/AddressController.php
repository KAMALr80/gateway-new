<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = Address::where('user_id', auth('api')->id())
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $addresses->map(fn($a) => $this->formatAddress($a)),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $userId = auth('api')->id();
        $data   = $request->validated();

        $hasExisting = Address::where('user_id', $userId)->exists();
        $makeDefault = !$hasExisting || ($data['is_default'] ?? false);

        $address = DB::transaction(function () use ($userId, $data, $makeDefault) {
            if ($makeDefault) {
                Address::where('user_id', $userId)->update(['is_default' => false]);
            }

            return Address::create(array_merge($data, [
                'user_id'    => $userId,
                'is_default' => $makeDefault,
            ]));
        });

        return response()->json([
            'message' => 'Address created successfully',
            'data'    => $this->formatAddress($address),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $address = Address::where('user_id', auth('api')->id())->findOrFail($id);

        return response()->json($this->formatAddress($address));
    }

    public function update(UpdateAddressRequest $request, int $id): JsonResponse
    {
        $userId  = auth('api')->id();
        $address = Address::where('user_id', $userId)->findOrFail($id);
        $data    = $request->validated();

        $address = DB::transaction(function () use ($userId, $address, $data) {
            if (($data['is_default'] ?? false) === true) {
                Address::where('user_id', $userId)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            return $address;
        });

        return response()->json([
            'message' => 'Address updated successfully',
            'data'    => $this->formatAddress($address->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $address = Address::where('user_id', auth('api')->id())->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }

    private function formatAddress(Address $address): array
    {
        return [
            'id'         => $address->id,
            'label'      => $address->label,
            'first_name' => $address->first_name,
            'last_name'  => $address->last_name,
            'company'    => $address->company,
            'address_1'  => $address->address_1,
            'address_2'  => $address->address_2,
            'city'       => $address->city,
            'state'      => $address->state,
            'postcode'   => $address->postcode,
            'country'    => $address->country,
            'phone'      => $address->phone,
            'is_default' => $address->is_default,
        ];
    }
}
