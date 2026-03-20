<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(protected CacheService $cache) {}

    public function index(Request $request): JsonResponse
    {
        // Build a fingerprint from query params so each
        // unique filter combination has its own cache entry
        $fingerprint = md5(json_encode($request->only([
            'category_id',
            'sub_category_id',
            'brand_id',
            'type',
            'in_stock',
            'search',
            'sort',
            'per_page',
            'page',
        ])));

        $cached = $this->cache->getProductList($fingerprint);

        if ($cached) {
            return response()->json($cached);
        }

        $query = Product::query()
            ->with(['category', 'subCategory', 'brand', 'primaryImage'])
            ->where('is_active', true);

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('in_stock')) {
            $query->where('in_stock', true);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        match ($request->get('sort', 'default')) {
            'price_asc'  => $query->orderByRaw('COALESCE(sale_price, regular_price) ASC'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, regular_price) DESC'),
            'newest'     => $query->orderBy('created_at', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            default      => $query->orderBy('name', 'asc'),
        };

        $perPage  = min((int) $request->get('per_page', 20), 100);
        $products = $query->paginate($perPage);

        $data = [
            'data' => $products->map(fn($p) => $this->formatProduct($p)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'last_page'    => $products->lastPage(),
            ],
        ];

        $this->cache->putProductList($fingerprint, $data);

        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        $cached = $this->cache->getProduct($id);

        if ($cached) {
            return response()->json($cached);
        }

        $product = Product::with([
            'category',
            'subCategory',
            'brand',
            'media',
        ])
        ->where('is_active', true)
        ->findOrFail($id);

        $data = $this->formatProduct($product, detailed: true);

        $this->cache->putProduct($id, $data);

        return response()->json($data);
    }

    private function formatProduct(Product $product, bool $detailed = false): array
    {
        $data = [
            'id'            => $product->id,
            'name'          => $product->name,
            'slug'          => $product->slug,
            'type'          => $product->type,
            'sku'           => $product->sku,
            'regular_price' => $product->regular_price,
            'sale_price'    => $product->sale_price,
            'current_price' => $product->current_price,
            'on_sale'       => $product->isOnSale(),
            'in_stock'      => $product->in_stock,
            'stock_quantity'=> $product->manage_stock ? $product->stock_quantity : null,
            'image'         => $product->primaryImage?->public_url,
            'category'      => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'sub_category'  => $product->subCategory ? [
                'id'   => $product->subCategory->id,
                'name' => $product->subCategory->name,
                'slug' => $product->subCategory->slug,
            ] : null,
            'brand'         => $product->brand ? [
                'id'   => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
        ];

        if ($detailed) {
            $data['description']       = $product->description;
            $data['short_description'] = $product->short_description;
            $data['attributes']        = $product->attributes;
            $data['images']            = $product->media->map(fn($m) => [
                'url'        => $m->public_url,
                'thumbnail'  => $m->thumbnail_url,
                'alt'        => $m->alt,
                'is_primary' => $m->is_primary,
            ]);
        }

        return $data;
    }
}