<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('API_BASE_URL', 'http://localhost:8000/api');
    }

    public function showLogin()
    {
        $captcha = $this->getCaptcha();
        return view('auth.login', compact('captcha'));
    }

    public function showRegister()
    {
        $captcha = $this->getCaptcha();
        return view('auth.register', compact('captcha'));
    }

    private function getCaptcha()
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . '/captcha');
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('Captcha fetch error: ' . $e->getMessage());
        }

        // Fallback captcha
        return [
            'question' => 'What is 5 + 3?',
            'token' => 'fallback_token_' . time(),
            'answer' => '8'
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha_answer' => 'required',
            'captcha_token' => 'required',
        ]);

        try {
            $response = Http::post($this->apiBaseUrl . '/login', [
                'email' => $request->email,
                'password' => $request->password,
                'captcha_answer' => $request->captcha_answer,
                'captcha_token' => $request->captcha_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Session::put('api_token', $data['token']);
                Session::put('user', $data['user']);
                
                return redirect()->route('dashboard');
            }

            $error = $response->json('error', 'Invalid credentials or captcha');
            return back()->withErrors(['email' => $error]);

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Unable to connect to server. Please try again.']);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'captcha_answer' => 'required',
            'captcha_token' => 'required',
        ]);

        try {
            $response = Http::post($this->apiBaseUrl . '/register', [
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'captcha_answer' => $request->captcha_answer,
                'captcha_token' => $request->captcha_token,
            ]);

            if ($response->successful()) {
                return redirect()->route('login')->with('success', 'Registration successful! Please login.');
            }

            $errors = $response->json('errors') ?? ['registration' => ['Registration failed']];
            return back()->withErrors($errors);

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Unable to connect to server. Please try again.']);
        }
    }

    public function logout()
    {
        $token = Session::get('api_token');
        
        if ($token) {
            try {
                Http::withToken($token)->post($this->apiBaseUrl . '/logout');
            } catch (\Exception $e) {
                // Log error but continue with logout
                \Log::error('Logout error: ' . $e->getMessage());
            }
        }

        Session::flush();
        return redirect()->route('login');
    }

    public function refreshCaptcha()
    {
        $captcha = $this->getCaptcha();
        return response()->json($captcha);
    }
}