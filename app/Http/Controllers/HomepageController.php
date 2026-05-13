<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageConfig;
use App\Models\Product;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function show(Request $request)
    {
        $clientKey = $request->input('client');

        if (! $clientKey) {
            return response()->json(['message' => 'The client parameter is required.'], 422);
        }

        $config = HomepageConfig::where('client_key', $clientKey)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return response()->json(['message' => 'Homepage config not found.'], 404);
        }

        $sections = $config->activeSections()->get();

        $resolved = $sections->map(fn ($section) => [
            'type'     => $section->type,
            'title'    => $section->title,
            'settings' => $section->settings,
            'data'     => $this->resolveData($section->type, $section->settings ?? []),
        ]);

        return response()->json([
            'client'   => $config->client_key,
            'label'    => $config->label,
            'sections' => $resolved,
        ]);
    }

    private function resolveData(string $type, array $settings): array|null
    {
        return match ($type) {
            'featured_category' => $this->resolveFeaturedCategory($settings),
            'brand_showcase'    => $this->resolveBrandShowcase($settings),
            default             => null,
        };
    }

    private function resolveFeaturedCategory(array $settings): array|null
    {
        $categoryId = $settings['category_id'] ?? null;
        if (! $categoryId) {
            return null;
        }

        $category = Category::select('id', 'name', 'slug', 'description')
            ->with('primaryImage')
            ->find($categoryId);

        if (! $category) {
            return null;
        }

        $limit    = (int) ($settings['product_limit'] ?? 8);
        $products = Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->where('in_stock', true)
            ->whereNull('parent_id')
            ->with('primaryImage')
            ->select('id', 'name', 'slug', 'sku', 'regular_price', 'sale_price', 'in_stock')
            ->limit($limit)
            ->get();

        return [
            'category' => $category,
            'products' => $products,
        ];
    }

    private function resolveBrandShowcase(array $settings): array|null
    {
        $brandIds = $settings['brand_ids'] ?? [];
        if (empty($brandIds)) {
            return null;
        }

        $brands = Brand::whereIn('id', $brandIds)
            ->where('is_active', true)
            ->with('primaryImage')
            ->select('id', 'name', 'slug')
            ->get()
            ->sortBy(fn ($b) => array_search($b->id, $brandIds))
            ->values();

        return ['brands' => $brands];
    }
}
