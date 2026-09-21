<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Brand::withCount('products');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $brands = $query->with('primaryImage')->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('admin/brands/index', [
            'brands'  => $this->paginatedData($brands),
            'filters' => $request->only(['search', 'is_active', 'per_page']),
        ]);
    }

    public function show(Brand $brand)
    {
        $brand->loadCount('products');
        $brand->load('media');

        return Inertia::render('admin/brands/show', [
            'brand' => $brand,
        ]);
    }
}
