<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderToErp;
use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Re-queues orders that never reached the ERP (e.g. the queue worker was not running, or the ERP was down
 * longer than the job's retries). Safe to run repeatedly: SendOrderToErp skips orders that are already synced,
 * and recently attempted orders are left alone so a job that is still queued is not duplicated.
 */
class RetryErpOrders extends Command
{
    protected $signature = 'erp:retry-orders
        {--include-failed : Also re-queue orders marked failed after all attempts (resets their attempt count)}
        {--older-than=30 : Only orders whose last attempt (or creation) is at least this many minutes old}
        {--dry-run : List the orders without queueing them}';

    protected $description = 'Re-queue orders that have not been synced to the ERP';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('older-than')));
        $statuses = $this->option('include-failed') ? ['pending', 'syncing', 'failed'] : ['pending', 'syncing'];

        $orders = Order::whereIn('sync_status', $statuses)
            ->where(fn ($q) => $q->where('last_sync_attempt_at', '<=', $cutoff)
                ->orWhere(fn ($q) => $q->whereNull('last_sync_attempt_at')->where('created_at', '<=', $cutoff)))
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders waiting for ERP sync.');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $this->line("Order #{$order->id} {$order->invoice_no} ({$order->sync_status}, attempts {$order->sync_attempts})");
            if ($this->option('dry-run')) {
                continue;
            }
            if ($order->sync_status === 'failed') {
                $order->update(['sync_status' => 'pending', 'sync_attempts' => 0]);
            }
            SendOrderToErp::dispatch($order);
        }

        $this->info($this->option('dry-run') ? "{$orders->count()} order(s) would be queued." : "{$orders->count()} order(s) queued.");

        return self::SUCCESS;
    }
}
