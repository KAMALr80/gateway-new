<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderStatusSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'orders'                  => ['required', 'array'],
            'orders.*.erp_order_id'   => ['required', 'integer'],
            'orders.*.status'         => ['required', 'string'],
            'orders.*.payment_status' => ['sometimes', 'string'],
        ]);

        $synced = 0;
        $errors = [];

        foreach ($request->orders as $data) {
            try {
                $updated = Order::where('erp_order_id', $data['erp_order_id'])
                    ->update([
                        'status'         => $data['status'],
                        'payment_status' => $data['payment_status'] ?? null,
                    ]);

                if (!$updated) {
                    $errors[] = [
                        'erp_order_id' => $data['erp_order_id'],
                        'error'        => 'Order not found',
                    ];
                    continue;
                }

                $synced++;

            } catch (\Throwable $e) {
                $errors[] = [
                    'erp_order_id' => $data['erp_order_id'],
                    'error'        => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Synced {$synced} order statuses",
            'errors'  => $errors,
        ]);
    }
}