@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('login') }}" method="POST" id="loginForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="captcha_answer" class="form-label">
                            <span id="captcha-question">{{ $captcha['question'] ?? 'What is 5 + 3?' }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="refreshCaptcha()">
                                ↻ Refresh
                            </button>
                        </label>
                        <input type="text" class="form-control" id="captcha_answer" name="captcha_answer" required>
                        <input type="hidden" id="captcha_token" name="captcha_token" value="{{ $captcha['token'] ?? 'fallback_token' }}">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('register') }}">Don't have an account? Register</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshCaptcha() {
    fetch('{{ route("refresh-captcha") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('captcha-question').textContent = data.question;
            document.getElementById('captcha_token').value = data.token;
            document.getElementById('captcha_answer').value = '';
        })
        .catch(error => {
            console.error('Error refreshing captcha:', error);
            // Fallback captcha
            document.getElementById('captcha-question').textContent = 'What is 7 + 2?';
            document.getElementById('captcha_token').value = 'fallback_' + Date.now();
        });
}

// Refresh captcha if there was an error and page reloaded
document.addEventListener('DOMContentLoaded', function() {
    @if($errors->any())
    refreshCaptcha();
    @endif
});
</script>
@endsection