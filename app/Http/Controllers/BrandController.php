<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    public function index(): JsonResponse
    {
        $cached = $this->cache->getBrands();

        if ($cached) {
            return response()->json($cached);
        }

        $brands = Brand::with('primaryImage')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => [
                'id'    => $b->id,
                'name'  => $b->name,
                'slug'  => $b->slug,
                'image' => $b->primaryImage?->public_url,
            ])->values()->all();

        $data = ['data' => $brands];

        $this->cache->putBrands($data);

        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        $cached = $this->cache->getBrand($id);

        if ($cached) {
            return response()->json($cached);
        }

        $brand = Brand::with(['primaryImage', 'media'])
            ->where('is_active', true)
            ->findOrFail($id);

        $data = [
            'id'          => $brand->id,
            'name'        => $brand->name,
            'slug'        => $brand->slug,
            'description' => $brand->description,
            'image'       => $brand->primaryImage?->public_url,
        ];

        $this->cache->putBrand($id, $data);

        return response()->json($data);
    }
}
