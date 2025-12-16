<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Order;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DisputeController extends Controller
{
    public function __construct(
        private DisputeService $disputeService
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        $query = Dispute::query();

        if (!$user->isAdmin()) {
            $query->whereHas('order', function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        $disputes = $query->with(['order', 'openedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('disputes.index', compact('disputes'));
    }

    public function show(Dispute $dispute)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            $order = $dispute->order;
            if ($order->buyer_id !== $user->id && $order->seller_id !== $user->id) {
                abort(403);
            }
        }

        $dispute->load(['order', 'openedBy', 'evidences', 'messages.user']);

        return view('disputes.show', compact('dispute'));
    }

    public function create(Order $order)
    {
        if (!$order->canBeDisputed()) {
            return back()->with('error', __('This order cannot be disputed.'));
        }

        return view('disputes.create', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'type' => 'required|in:buyer,seller',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        try {
            $dispute = $this->disputeService->openDispute(
                $order,
                auth()->user(),
                $validated['type'],
                $validated['reason'],
                $validated['description']
            );

            return redirect()->route('disputes.show', $dispute)
                ->with('success', __('Dispute opened successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function addEvidence(Request $request, Dispute $dispute)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store('disputes', 'public');

        $this->disputeService->addEvidence(
            $dispute,
            auth()->user(),
            $path,
            $file->getClientOriginalName(),
            $file->getMimeType(),
            $file->getSize(),
            $validated['description'] ?? null
        );

        return back()->with('success', __('Evidence added.'));
    }

    public function addMessage(Request $request, Dispute $dispute)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $this->disputeService->addMessage(
            $dispute,
            auth()->user(),
            $validated['message'],
            false
        );

        return back()->with('success', __('Message added.'));
    }
}

