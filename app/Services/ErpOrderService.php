<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpOrderService
{
    private string $webhookUrl;
    private string $secret;

    public function __construct()
    {
        $this->webhookUrl = config('services.erp.order_webhook_url');
        $this->secret     = config('services.erp.webhook_secret');
    }

    public function sendOrder(Order $order): bool
    {
        $payload   = $this->buildPayload($order);
        $signature = $this->sign($payload);

        try {
            $response = Http::withHeaders([
                'Content-Type'       => 'application/json',
                'X-Webhook-Secret'   => $signature,
            ])
            ->timeout(30)
            ->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();

                $order->update([
                    'sync_status'  => 'synced',
                    'erp_order_id' => $body['erp_order_id'] ?? null,
                    'synced_at'    => now(),
                    'sync_error'   => null,
                ]);

                return true;
            }

            $this->markFailed($order, "ERP returned HTTP {$response->status()}: {$response->body()}");
            return false;

        } catch (\Throwable $e) {
            $this->markFailed($order, $e->getMessage());
            return false;
        }
    }

    private function buildPayload(Order $order): array
    {
        $order->loadMissing(['items', 'user']);

        return [
            'id'                   => $order->id,
            'invoice_no'           => $order->invoice_no,
            'order_key'            => $order->order_key,
            'created_via'          => $order->created_via,
            'transaction_date'     => $order->transaction_date?->toIso8601String(),
            'status'               => $order->status,
            'payment_status'       => $order->payment_status,
            'currency'             => $order->currency,
            'line_total'           => $order->line_total,
            'discount_total'       => $order->discount_total,
            'shipping_total'       => $order->shipping_total,
            'total_tax'            => $order->total_tax,
            'total'                => $order->total,
            'customer_id'          => $order->erp_customer_id,
            'customer_note'        => $order->customer_note,
            'customer_ip_address'  => $order->customer_ip_address,
            'billing_first_name'   => $order->billing_first_name,
            'billing_last_name'    => $order->billing_last_name,
            'billing_company'      => $order->billing_company,
            'billing_address_1'    => $order->billing_address_1,
            'billing_address_2'    => $order->billing_address_2,
            'billing_city'         => $order->billing_city,
            'billing_state'        => $order->billing_state,
            'billing_postcode'     => $order->billing_postcode,
            'billing_country'      => $order->billing_country,
            'billing_email'        => $order->billing_email,
            'billing_phone'        => $order->billing_phone,
            'shipping_first_name'  => $order->shipping_first_name,
            'shipping_last_name'   => $order->shipping_last_name,
            'shipping_company'     => $order->shipping_company,
            'shipping_address_1'   => $order->shipping_address_1,
            'shipping_address_2'   => $order->shipping_address_2,
            'shipping_city'        => $order->shipping_city,
            'shipping_state'       => $order->shipping_state,
            'shipping_postcode'    => $order->shipping_postcode,
            'shipping_country'     => $order->shipping_country,
            'meta_data'            => $order->meta_data ?? [],
            'line_items'           => $order->items->map(fn($item) => [
                'product_id'     => $item->erp_product_id,
                'name'           => $item->name,
                'sku'            => $item->sku,
                'quantity'       => $item->quantity,
                'unit_price'     => $item->unit_price,
                'unit_tax'       => $item->unit_tax,
                'total'          => $item->total,
            ])->toArray(),
        ];
    }

    private function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    private function markFailed(Order $order, string $error): void
    {
        Log::error('ERP order sync failed', [
            'order_id' => $order->id,
            'attempt'  => $order->sync_attempts + 1,
            'error'    => $error,
        ]);

        $order->update([
            'sync_status'           => $order->sync_attempts + 1 >= 3 ? 'failed' : 'pending',
            'sync_attempts'         => $order->sync_attempts + 1,
            'last_sync_attempt_at'  => now(),
            'sync_error'            => $error,
        ]);
    }
}