<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('API_BASE_URL', 'http://localhost:8000/api');
    }

    public function index(Request $request)
    {
        $token = Session::get('api_token');
        
        // Validate and sanitize page parameter
        $page = max((int)$request->get('page', 1), 1);
        $search = $request->get('search');

        // Check if user is authenticated
        if (!$token || !Session::has('user')) {
            Session::flash('error', 'Please login to access this page.');
            return redirect()->route('login');
        }

        try {
            $url = $search 
                ? $this->apiBaseUrl . "/admin/users/search?q={$search}&page={$page}"
                : $this->apiBaseUrl . "/admin/users?page={$page}";

            \Log::info("Frontend API Call: " . $url);

            $response = Http::withToken($token)->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $users = $data['users'] ?? [];
                $pagination = $data['pagination'] ?? [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0
                ];

                // If current page is invalid, redirect to page 1
                if ($pagination['current_page'] > $pagination['last_page'] && $pagination['last_page'] > 0) {
                    return redirect()->route('admin.users.index', ['page' => 1, 'search' => $search]);
                }

                \Log::info("Users fetched successfully", [
                    'count' => count($users),
                    'current_page' => $pagination['current_page'],
                    'total' => $pagination['total']
                ]);
            } else {
                $users = [];
                $pagination = [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0
                ];
                
                if ($response->status() === 401) {
                    Session::flash('error', 'Session expired. Please login again.');
                    Session::forget(['api_token', 'user']);
                    return redirect()->route('login');
                } else {
                    Session::flash('error', 'Failed to fetch users. API returned: ' . $response->status());
                }
            }
        } catch (\Exception $e) {
            \Log::error("API Connection Error: " . $e->getMessage());
            $users = [];
            $pagination = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'from' => 0,
                'to' => 0
            ];
            Session::flash('error', 'Unable to connect to the API server: ' . $e->getMessage());
        }

        return view('admin.users.index', compact('users', 'pagination', 'search'));
    }

    public function create()
    {
        if (!Session::has('api_token') || !Session::has('user')) {
            Session::flash('error', 'Please login to access this page.');
            return redirect()->route('login');
        }

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $token = Session::get('api_token');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,manager,user',
        ]);

        try {
            $response = Http::withToken($token)
                ->post($this->apiBaseUrl . '/admin/users', $request->all());

            if ($response->successful()) {
                Session::flash('success', 'User created successfully!');
                return redirect()->route('admin.users.index');
            }

            $errors = $response->json('errors') ?? ['error' => ['Failed to create user']];
            return back()->withErrors($errors)->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to connect to the API server'])->withInput();
        }
    }

    public function edit($id)
    {
        $token = Session::get('api_token');
        
        try {
            $response = Http::withToken($token)->get($this->apiBaseUrl . "/admin/users/{$id}");

            if ($response->successful()) {
                $user = $response->json();
                return view('admin.users.edit', compact('user'));
            }

            Session::flash('error', 'User not found');
            return redirect()->route('admin.users.index');

        } catch (\Exception $e) {
            Session::flash('error', 'Unable to connect to the API server');
            return redirect()->route('admin.users.index');
        }
    }

public function update(Request $request, $id)
{
    $token = Session::get('api_token');
    
    \Log::info('=== FRONTEND USER UPDATE START ===');
    \Log::info('Frontend update request for user ID: ' . $id);
    \Log::info('Request data:', $request->all());
    \Log::info('Session user:', Session::get('user'));

    // Validate request
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'password' => 'nullable|min:8',
        'role' => 'required|in:admin,manager,user',
    ]);

    // Check authentication
    if (!$token) {
        \Log::error('No API token found in session');
        Session::flash('error', 'Authentication required. Please login again.');
        return redirect()->route('login');
    }

    try {
        // Prepare data for API
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        // Only include password if provided and not empty
        if ($request->filled('password')) {
            $data['password'] = $request->password;
            \Log::info('Password included in update');
        } else {
            \Log::info('Password not included in update');
        }

        \Log::info('Sending update to API:', $data);

        $apiUrl = $this->apiBaseUrl . "/admin/users/{$id}";
        \Log::info('API URL: ' . $apiUrl);

        $response = Http::withToken($token)
            ->timeout(30)
            ->put($apiUrl, $data);

        \Log::info('API Response Status: ' . $response->status());
        \Log::info('API Response Body: ' . $response->body());

        if ($response->successful()) {
            \Log::info('=== FRONTEND USER UPDATE SUCCESS ===');
            Session::flash('success', 'User updated successfully!');
            return redirect()->route('admin.users.index');
        }

        // Handle different error scenarios
        $statusCode = $response->status();
        $responseData = $response->json();

        \Log::error('API returned error:', [
            'status' => $statusCode,
            'response' => $responseData
        ]);

        switch ($statusCode) {
            case 401:
                Session::flash('error', 'Session expired. Please login again.');
                Session::forget(['api_token', 'user']);
                return redirect()->route('login');
                
            case 403:
                Session::flash('error', 'You do not have permission to update users.');
                return back()->withInput();
                
            case 422:
                $errors = $responseData['errors'] ?? ['error' => ['Validation failed']];
                return back()->withErrors($errors)->withInput();
                
            default:
                $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Failed to update user';
                return back()->withErrors(['error' => $errorMessage])->withInput();
        }

    } catch (\Exception $e) {
        \Log::error('=== FRONTEND USER UPDATE EXCEPTION ===');
        \Log::error('Exception: ' . $e->getMessage());
        \Log::error('Trace: ' . $e->getTraceAsString());

        return back()->withErrors([
            'error' => 'Unable to connect to the API server: ' . $e->getMessage()
        ])->withInput();
    }
}

    public function destroy($id)
    {
        $token = Session::get('api_token');
        
        try {
            $response = Http::withToken($token)
                ->delete($this->apiBaseUrl . "/admin/users/{$id}");

            if ($response->successful()) {
                Session::flash('success', 'User deleted successfully!');
            } else {
                Session::flash('error', 'Failed to delete user');
            }

        } catch (\Exception $e) {
            Session::flash('error', 'Unable to connect to the API server');
        }

        return redirect()->route('admin.users.index');
    }
}