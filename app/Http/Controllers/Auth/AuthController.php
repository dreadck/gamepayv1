<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private ActivityLogService $activityLogService
    ) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->is_banned) {
                Auth::logout();
                return back()->with('error', __('Your account has been banned.'));
            }

            $user->updateActivity();
            $this->activityLogService->log('user.login', $user, $user->id, 'User logged in', null, $request);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->with('error', __('auth.failed'))->withInput();
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'nullable|string|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'nullable|in:buyer,seller',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username ?? null,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'buyer',
        ]);

        $this->walletService->getOrCreateWallet($user);
        $this->activityLogService->log('user.register', $user, $user->id, 'User registered', null, $request);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', __('Registration successful!'));
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        
        if ($user) {
            $this->activityLogService->log('user.logout', $user, $user->id, 'User logged out', null, $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('You have been logged out.'));
    }
}

