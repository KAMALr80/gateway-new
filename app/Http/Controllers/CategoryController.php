<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    public function index(): JsonResponse
    {
        $cached = $this->cache->getCategories();

        if ($cached) {
            return response()->json($cached);
        }

        $categories = Category::query()
            ->with(['children', 'primaryImage'])
            ->whereNull('parent_id')        // top-level only — children nested inside
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => $this->formatCategory($c))
            ->values()->all();

        $data = ['data' => $categories];

        $this->cache->putCategories($data);

        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        $cached = $this->cache->getCategory($id);

        if ($cached) {
            return response()->json($cached);
        }

        $category = Category::with(['parent', 'children', 'primaryImage'])
            ->where('is_active', true)
            ->findOrFail($id);

        $data = $this->formatCategory($category, detailed: true);

        $this->cache->putCategory($id, $data);

        return response()->json($data);
    }

    private function formatCategory(Category $category, bool $detailed = false): array
    {
        $data = [
            'id'          => $category->id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'sort_order'  => $category->sort_order,
            'image'       => $category->primaryImage?->public_url,
            'children'    => $category->children
                                ->where('is_active', true)
                                ->map(fn($c) => [
                                    'id'   => $c->id,
                                    'name' => $c->name,
                                    'slug' => $c->slug,
                                    'image' => $c->primaryImage?->public_url,
                                ])->values()->all(),
        ];

        if ($detailed) {
            $data['description'] = $category->description;
            $data['parent']      = $category->parent ? [
                'id'   => $category->parent->id,
                'name' => $category->parent->name,
                'slug' => $category->parent->slug,
            ] : null;
        }

        return $data;
    }
}
