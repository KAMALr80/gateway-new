<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Product::with(['category', 'brand', 'primaryImage']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->orWhere('sub_category_id', $categoryId);
            });
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($request->input('in_stock') !== null && $request->input('in_stock') !== '') {
            $query->where('in_stock', filter_var($request->input('in_stock'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $products = $query->latest()->paginate($perPage)->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/products/index', [
            'products'   => $this->paginatedData($products),
            'categories' => $categories,
            'brands'     => $brands,
            'filters'    => $request->only(['search', 'category_id', 'brand_id', 'type', 'in_stock', 'is_active', 'per_page']),
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'category',
            'subCategory',
            'brand',
            'parent',
            'children' => fn ($q) => $q->with('primaryImage'),
            'media',
        ]);

        $wishlistCount = WishlistItem::where('product_id', $product->id)->count();

        return Inertia::render('admin/products/show', [
            'product'       => $product,
            'wishlistCount' => $wishlistCount,
        ]);
    }
}
