<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\EscrowService;
use Illuminate\Console\Command;

class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';
    protected $description = 'Auto-complete orders after delivery period';

    public function __construct(
        private EscrowService $escrowService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orders = Order::where('status', 'delivered')
            ->where('auto_complete_enabled', true)
            ->whereNotNull('delivered_at')
            ->get();

        foreach ($orders as $order) {
            $hours = $order->auto_complete_hours ?? 72;
            $deliveredAt = $order->delivered_at;

            if ($deliveredAt->addHours($hours)->isPast()) {
                try {
                    $this->escrowService->autoRelease($order);
                    $order->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    $this->info("Order {$order->order_number} auto-completed");
                } catch (\Exception $e) {
                    $this->error("Failed to auto-complete order {$order->order_number}: {$e->getMessage()}");
                }
            }
        }

        return Command::SUCCESS;
    }
}

