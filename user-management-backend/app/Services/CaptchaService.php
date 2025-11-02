<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CaptchaService
{
    public function generate(): array
    {
        $num1 = rand(1, 15);
        $num2 = rand(1, 15);
        $answer = $num1 + $num2;

        $token = 'captcha_' . md5($answer . time() . uniqid());

        // Store answer in cache for 5 minutes
        Cache::put('captcha_' . $token, (string)$answer, 300);

        return [
            'question' => "What is $num1 + $num2?",
            'token' => $token,
            'answer' => (string)$answer,
        ];
    }

    public function validate(string $token, string $answer): bool
    {
        $expectedAnswer = Cache::get('captcha_' . $token);
        
        if (!$expectedAnswer) {
            return false;
        }

        Cache::forget('captcha_' . $token);
        
        return $expectedAnswer === $answer;
    }
}