<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ErpOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderToErp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    // Backoff in seconds between retries: 1min, 5min, 15min
    public array $backoff = [60, 300, 900];

    public function __construct(public Order $order) {}

    public function handle(ErpOrderService $erpService): void
    {
        // Skip if already synced (e.g. duplicate job dispatch)
        if ($this->order->isSynced()) {
            return;
        }

        $this->order->update([
            'sync_status' => 'syncing',
        ]);

        // sendOrder() records the failure on the order and returns false instead of throwing. Throwing here
        // is what makes the queue retry with $backoff; without it a single ERP outage left the order
        // 'pending' forever and it never reached the ERP.
        if (! $erpService->sendOrder($this->order)) {
            throw new \RuntimeException("ERP sync failed for order {$this->order->id}: {$this->order->sync_error}");
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->order->update([
            'sync_status' => 'failed',
            'sync_error'  => $e->getMessage(),
        ]);
    }
}