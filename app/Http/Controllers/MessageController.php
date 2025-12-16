<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $conversations = Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1', 'user2', 'order'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('messages.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        if ($conversation->user1_id !== $user->id && $conversation->user2_id !== $user->id) {
            abort(403);
        }

        $conversation->load(['user1', 'user2', 'order']);
        $messages = $conversation->messages()->with(['sender', 'attachments'])->get();

        // Mark messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('messages.show', compact('conversation', 'messages'));
    }

    public function store(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        if ($conversation->user1_id !== $user->id && $conversation->user2_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'attachments.*' => 'file|max:10240',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'content' => $validated['content'],
            'type' => 'text',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('messages', 'public');
                
                $message->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', __('Message sent.'));
    }

    public function getOrCreateConversation(User $otherUser, ?Order $order = null): Conversation
    {
        $user = auth()->user();

        $conversation = Conversation::where(function ($query) use ($user, $otherUser, $order) {
            $query->where('user1_id', $user->id)
                  ->where('user2_id', $otherUser->id);
        })->orWhere(function ($query) use ($user, $otherUser, $order) {
            $query->where('user1_id', $otherUser->id)
                  ->where('user2_id', $user->id);
        })->when($order, function ($query) use ($order) {
            $query->where('order_id', $order->id);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user1_id' => $user->id,
                'user2_id' => $otherUser->id,
                'order_id' => $order?->id,
            ]);
        }

        return $conversation;
    }
}

