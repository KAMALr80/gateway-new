<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Category::withCount('products')->with('parent');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($parentId = $request->input('parent_id')) {
            if ($parentId === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        if ($request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)->withQueryString();

        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/categories/index', [
            'categories'       => $this->paginatedData($categories),
            'parentCategories' => $parentCategories,
            'filters'          => $request->only(['search', 'parent_id', 'is_active', 'per_page']),
        ]);
    }

    public function show(Category $category)
    {
        $category->loadCount('products');
        $category->load(['parent', 'children', 'primaryImage']);

        return Inertia::render('admin/categories/show', [
            'category' => $category,
        ]);
    }
}
