<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Services\EscrowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeService
{
    public function __construct(
        private EscrowService $escrowService
    ) {}

    public function openDispute(Order $order, User $openedBy, string $type, string $reason, string $description): Dispute
    {
        return DB::transaction(function () use ($order, $openedBy, $type, $reason, $description) {
            if (!$order->canBeDisputed()) {
                throw new \Exception('Order cannot be disputed');
            }

            if ($order->dispute) {
                throw new \Exception('Dispute already exists for this order');
            }

            $order->update(['status' => 'disputed']);

            $dispute = Dispute::create([
                'order_id' => $order->id,
                'opened_by' => $openedBy->id,
                'status' => 'open',
                'type' => $type,
                'reason' => $reason,
                'description' => $description,
            ]);

            Log::info('Dispute opened', [
                'dispute_id' => $dispute->id,
                'order_id' => $order->id,
                'opened_by' => $openedBy->id,
            ]);

            return $dispute;
        });
    }

    public function resolveDispute(
        Dispute $dispute,
        User $admin,
        string $resolution,
        ?float $refundAmount = null,
        string $notes = null
    ): void {
        DB::transaction(function () use ($dispute, $admin, $resolution, $refundAmount, $notes) {
            if (!$dispute->isOpen()) {
                throw new \Exception('Dispute is not open');
            }

            $order = $dispute->order;

            $dispute->update([
                'status' => 'resolved',
                'resolution' => $resolution,
                'refund_amount' => $refundAmount,
                'resolved_by' => $admin->id,
                'resolution_notes' => $notes,
                'resolved_at' => now(),
            ]);

            switch ($resolution) {
                case 'buyer_favor':
                case 'full_refund':
                    $this->escrowService->refundToBuyer(
                        $order,
                        $admin,
                        "Dispute resolved: {$notes}"
                    );
                    $order->update(['status' => 'refunded']);
                    break;

                case 'seller_favor':
                    $this->escrowService->releaseToSeller(
                        $order,
                        $admin,
                        "Dispute resolved in seller's favor: {$notes}"
                    );
                    $order->update(['status' => 'completed']);
                    break;

                case 'partial_refund':
                    if ($refundAmount) {
                        // Partial refund logic
                        $this->escrowService->refundToBuyer(
                            $order,
                            $admin,
                            "Partial refund: {$notes}"
                        );
                    }
                    break;

                case 'dismissed':
                    $this->escrowService->releaseToSeller(
                        $order,
                        $admin,
                        "Dispute dismissed: {$notes}"
                    );
                    $order->update(['status' => 'completed']);
                    break;
            }

            Log::info('Dispute resolved', [
                'dispute_id' => $dispute->id,
                'resolution' => $resolution,
                'admin_id' => $admin->id,
            ]);
        });
    }

    public function addEvidence(Dispute $dispute, User $user, string $filePath, string $fileName, string $fileType, int $fileSize, string $description = null): void
    {
        $dispute->evidences()->create([
            'uploaded_by' => $user->id,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'description' => $description,
        ]);

        Log::info('Dispute evidence added', [
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
        ]);
    }

    public function addMessage(Dispute $dispute, User $user, string $message, bool $isInternal = false): void
    {
        $userType = $user->isAdmin() ? 'admin' : ($dispute->order->buyer_id === $user->id ? 'buyer' : 'seller');

        $dispute->messages()->create([
            'user_id' => $user->id,
            'user_type' => $userType,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);
    }
}

