<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users'      => User::count(),
                'orders'     => Order::count(),
                'products'   => Product::count(),
                'brands'     => Brand::count(),
                'categories' => Category::count(),
            ],
        ]);
    }
}
