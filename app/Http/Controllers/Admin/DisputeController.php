<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(
        private DisputeService $disputeService
    ) {}

    public function index(Request $request)
    {
        $query = Dispute::with(['order', 'openedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $disputes = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.disputes.index', compact('disputes'));
    }

    public function show(Dispute $dispute)
    {
        $dispute->load(['order', 'openedBy', 'resolvedBy', 'evidences', 'messages.user']);
        return view('admin.disputes.show', compact('dispute'));
    }

    public function resolve(Request $request, Dispute $dispute)
    {
        $validated = $request->validate([
            'resolution' => 'required|in:buyer_favor,seller_favor,partial_refund,full_refund,dismissed',
            'refund_amount' => 'nullable|numeric|min:0',
            'notes' => 'required|string|max:1000',
        ]);

        try {
            $this->disputeService->resolveDispute(
                $dispute,
                auth()->user(),
                $validated['resolution'],
                $validated['refund_amount'] ?? null,
                $validated['notes']
            );

            return back()->with('success', __('Dispute resolved successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

