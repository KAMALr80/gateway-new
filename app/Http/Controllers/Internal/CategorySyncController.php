<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CategorySyncController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'categories'                    => ['required', 'array'],
            'categories.*.erp_category_id'  => ['required', 'integer'],
            'categories.*.name'             => ['required', 'string'],
            'categories.*.parent_id'        => ['sometimes', 'nullable', 'integer'],
            'categories.*.is_active'        => ['sometimes', 'boolean'],
            'categories.*.sort_order'       => ['sometimes', 'integer'],
            'categories.*.image_url'        => ['sometimes', 'nullable', 'string'],
        ]);

        $synced = 0;
        $errors = [];

        // First pass — upsert all categories without parent resolution
        // so parent records exist before children reference them
        foreach ($request->categories as $data) {
            try {
                $category = Category::updateOrCreate(
                    ['erp_category_id' => $data['erp_category_id']],
                    [
                        'name'        => $data['name'],
                        'description' => $data['description'] ?? null,
                        'sort_order'  => $data['sort_order']  ?? 0,
                        'is_active'   => $data['is_active']   ?? true,
                    ]
                );

                if (!empty($data['image_url'])) {
                    $this->syncPrimaryImage($category, $data['image_url']);
                }

                $synced++;

            } catch (\Throwable $e) {
                $errors[] = [
                    'erp_category_id' => $data['erp_category_id'],
                    'error'           => $e->getMessage(),
                ];
            }
        }

        // Second pass — resolve parent_id relationships
        // using ERP IDs now that all records exist
        foreach ($request->categories as $data) {
            if (empty($data['parent_id'])) {
                continue;
            }

            try {
                $parent = Category::where('erp_category_id', $data['parent_id'])->first();

                if ($parent) {
                    Category::where('erp_category_id', $data['erp_category_id'])
                        ->update(['parent_id' => $parent->id]);
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'erp_category_id' => $data['erp_category_id'],
                    'error'           => 'Parent resolution failed: ' . $e->getMessage(),
                ];
            }
        }

        $this->cache->invalidateCategory(0);

        return response()->json([
            'message' => "Synced {$synced} categories",
            'errors'  => $errors,
        ]);
    }

    private function syncPrimaryImage(Category $category, string $url): void
    {
        Media::updateOrCreate(
            [
                'mediable_type' => Category::class,
                'mediable_id'   => $category->id,
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