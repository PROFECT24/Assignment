<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CaptchaController extends Controller
{
    public function simpleCaptcha(): JsonResponse
    {
        try {
            $num1 = rand(1, 10);
            $num2 = rand(1, 10);
            $answer = $num1 + $num2;
            $token = 'simple_' . time() . '_' . rand(1000, 9999);

            Log::info('Captcha generated', [
                'question' => "What is $num1 + $num2?",
                'token' => $token,
                'answer' => $answer
            ]);

            return response()->json([
                'success' => true,
                'question' => "What is $num1 + $num2?",
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error('Captcha error: ' . $e->getMessage());

            // Fallback
            return response()->json([
                'success' => true,
                'question' => 'What is 5 + 3?',
                'token' => 'fallback_' . time(),
            ]);
        }
    }
}