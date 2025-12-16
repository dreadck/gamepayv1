<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['wallet', 'products', 'ordersAsBuyer', 'ordersAsSeller']);
        return view('admin.users.show', compact('user'));
    }

    public function ban(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->ban($validated['reason']);

        return back()->with('success', __('User banned successfully.'));
    }

    public function unban(User $user)
    {
        $user->unban();
        return back()->with('success', __('User unbanned successfully.'));
    }

    public function freeze(User $user)
    {
        $user->freeze();
        return back()->with('success', __('User frozen successfully.'));
    }

    public function unfreeze(User $user)
    {
        $user->unfreeze();
        return back()->with('success', __('User unfrozen successfully.'));
    }
}

