<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Order::with('user')->withCount('items');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('billing_first_name', 'like', "%{$search}%")
                  ->orWhere('billing_last_name', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($syncStatus = $request->input('sync_status')) {
            $query->where('sync_status', $syncStatus);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/orders/index', [
            'orders'  => $this->paginatedData($orders),
            'filters' => $request->only(['search', 'status', 'payment_status', 'sync_status', 'date_from', 'date_to', 'per_page']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product']);

        return Inertia::render('admin/orders/show', [
            'order' => $order,
        ]);
    }
}
