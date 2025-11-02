<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('API_BASE_URL', 'http://localhost:8000/api');
        // Remove this line: $this->middleware('auth.frontend');
    }

    public function index()
    {
        // Manual middleware check
        if (!Session::has('api_token') || !Session::has('user')) {
            return redirect()->route('login');
        }

        $user = Session::get('user');
        return view('dashboard', compact('user'));
    }

    public function profile()
    {
        // Manual middleware check
        if (!Session::has('api_token') || !Session::has('user')) {
            return redirect()->route('login');
        }

        $user = Session::get('user');
        return view('profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        // Manual middleware check
        if (!Session::has('api_token') || !Session::has('user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $token = Session::get('api_token');
        
        $response = Http::withToken($token)
            ->put($this->apiBaseUrl . '/user/profile', [
                'name' => $request->name,
                'email' => $request->email,
            ]);

        if ($response->successful()) {
            $userData = $response->json('user');
            Session::put('user', $userData);
            return back()->with('success', 'Profile updated successfully!');
        }

        return back()->withErrors(['error' => 'Failed to update profile']);
    }
}