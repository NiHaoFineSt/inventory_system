<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        return view('login');
    }

    // Process login request
    // Process login request
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', 'in:staff,asset_team'],
        ]);

        // Attempt authentication using email & password
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Optional: Verify if the selected UI role matches the user's registered database role
            if ($user->role !== $request->role) {
                Auth::logout();
                return back()->withErrors([
                    'email' => "This account does not have permission to log in as " . ($request->role === 'asset_team' ? 'Asset Team' : 'General Staff') . ".",
                ])->onlyInput('email');
            }

            // Redirect based on selected/verified role
            if ($user->role === 'asset_team') {
                return redirect()->route('asset_dashboard');
            }

            return redirect()->route('staff_dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // Show registration page
    public function showRegister()
    {
        return view('register');
    }

    // Process registration request
    // Process registration request
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:staff,asset_team'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Redirect directly to login page with a success message
        return redirect()->route('login')->with('success', 'Account created successfully! Please sign in.');
    }
    // Process logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}