@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Dashboard</h4>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                </form>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5>Welcome, {{ $user['name'] }}!</h5>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">User Information</h6>
                                        <p class="mb-1"><strong>Email:</strong> {{ $user['email'] }}</p>
                                        <p class="mb-1"><strong>Role:</strong> 
                                            <span class="badge bg-{{ $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'primary') }}">
                                                {{ $user['role'] }}
                                            </span>
                                        </p>
                                        <p class="mb-0"><strong>Registered:</strong> {{ \Carbon\Carbon::parse($user['created_at'])->format('M j, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Quick Actions</h6>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('profile') }}" class="btn btn-outline-primary">Edit Profile</a>
                                            @if($user['role'] === 'admin')
                                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-success">
                                                    Manage Users
                                                </a>
                                            @endif
                                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5>Account Status</h5>
                                <div class="display-6">
                                    @if($user['role'] === 'admin')
                                        <i class="fas fa-shield-alt"></i>
                                    @elseif($user['role'] === 'manager')
                                        <i class="fas fa-user-tie"></i>
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                </div>
                                <p class="mt-2">Active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .display-6 {
        font-size: 3rem;
    }
</style>
@endsection