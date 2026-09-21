<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Media;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class BrandSyncController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'brands'                => ['required', 'array'],
            'brands.*.erp_brand_id' => ['required', 'integer'],
            'brands.*.name'         => ['required', 'string'],
            'brands.*.is_active'    => ['sometimes', 'boolean'],
            'brands.*.image_url'    => ['sometimes', 'nullable', 'string'],
        ]);

        $synced  = 0;
        $errors  = [];

        foreach ($request->brands as $data) {
            try {
                $brand = Brand::updateOrCreate(
                    ['erp_brand_id' => $data['erp_brand_id']],
                    [
                        'name'      => $data['name'],
                        'is_active' => $data['is_active'] ?? true,
                    ]
                );

                // Sync primary image if provided
                if (!empty($data['image_url'])) {
                    $this->syncPrimaryImage($brand, $data['image_url']);
                }

                $this->cache->invalidateBrand($brand->id);
                $synced++;

            } catch (\Throwable $e) {
                $errors[] = [
                    'erp_brand_id' => $data['erp_brand_id'],
                    'error'        => $e->getMessage(),
                ];
            }
        }

        // Invalidate brand list cache
        $this->cache->invalidateBrand(0);

        return response()->json([
            'message' => "Synced {$synced} brands",
            'errors'  => $errors,
        ]);
    }

    private function syncPrimaryImage(Brand $brand, string $url): void
    {
        Media::updateOrCreate(
            [
                'mediable_type' => Brand::class,
                'mediable_id'   => $brand->id,
                'is_primary'    => true,
            ],
            [
                'url'        => $url,
                'disk'       => 'external',
                'path'       => $url,
                'is_primary' => true,
                'sort_order' => 0,
            ]
        );
    }
}