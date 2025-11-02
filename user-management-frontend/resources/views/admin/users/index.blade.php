<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .btn-group-sm > .btn, .btn-sm {
            border-radius: 0.25rem;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .badge {
            font-size: 0.75em;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        /* Fix table header visibility with better contrast */
        .table-dark {
            --bs-table-bg: #343a40;
            --bs-table-striped-bg: #3e444a;
            --bs-table-striped-color: #fff;
            --bs-table-active-bg: #4a5056;
            --bs-table-active-color: #fff;
            --bs-table-hover-bg: #464c52;
            --bs-table-hover-color: #fff;
            color: #fff;
            border-color: #4a5056;
        }
        .table-dark th {
            background-color: #343a40;
            color: #ffffff;
            border-color: #454d55;
            font-weight: 600;
        }
        /* Ensure proper contrast for table rows */
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.02);
            color: var(--bs-table-color);
        }
        .table-hover > tbody > tr:hover > * {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.08);
            color: var(--bs-table-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">User Management</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                @if(session('user'))
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile') }}">Profile</a>
                    </li>
                    @if(session('user')['role'] === 'admin')
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('admin.users.index') }}">Manage Users</a>
                    </li>
                    @endif
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            {{ session('user')['name'] }} ({{ session('user')['role'] }})
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('profile') }}">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
                @endif
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </h4>
                        <div>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                            <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Add New User
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form action="{{ route('admin.users.index') }}" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, or role..." 
                                       value="{{ $search ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                                @if(!empty($search))
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                @endif
                            </div>
                        </form>

                        <!-- Users Count and Pagination Info -->
                        @if(isset($pagination) && isset($pagination['total']) && $pagination['total'] > 0)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Showing {{ $pagination['from'] ?? 0 }} to {{ $pagination['to'] ?? 0 }} 
                                    of {{ $pagination['total'] }} users
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="text-muted mb-0">
                                    Page {{ $pagination['current_page'] }} of {{ $pagination['last_page'] }}
                                </p>
                            </div>
                        </div>
                        @endif

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>ID 
                                            @if(empty($search))
                                            <i class="fas fa-sort-up ms-1"></i>
                                            @endif
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <strong>#{{ $user['id'] ?? 'N/A' }}</strong>
                                        </td>
                                        <td>{{ $user['name'] ?? 'N/A' }}</td>
                                        <td>{{ $user['email'] ?? 'N/A' }}</td>
                                        <td>
                                            @if(isset($user['role']))
                                            <span class="badge bg-{{ $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'primary') }}">
                                                {{ ucfirst($user['role']) }}
                                            </span>
                                            @else
                                            <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($user['created_at']))
                                                {{ \Carbon\Carbon::parse($user['created_at'])->format('M j, Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($user['id']))
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.users.edit', $user['id']) }}" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.users.destroy', $user['id']) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                            @else
                                            <span class="text-muted">No actions</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            @if(isset($users) && count($users) === 0)
                                                <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                                <p class="text-muted">No users found</p>
                                                @if(!empty($search))
                                                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm">
                                                        Show All Users
                                                    </a>
                                                @endif
                                            @else
                                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                                <p class="text-warning">Failed to load users. Please check your connection to the API server.</p>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if(isset($pagination) && isset($pagination['total']) && $pagination['total'] > 0)
                        <nav aria-label="User pagination">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Page Link -->
                                <li class="page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" 
                                       href="{{ route('admin.users.index', ['page' => $pagination['current_page'] - 1, 'search' => $search ?? '']) }}"
                                       aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>

                                <!-- Page Numbers - Always start from 1 -->
                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                        <a class="page-link" 
                                           href="{{ route('admin.users.index', ['page' => $i, 'search' => $search ?? '']) }}">
                                            {{ $i }}
                                        </a>
                                    </li>
                                @endfor

                                <!-- Next Page Link -->
                                <li class="page-item {{ $pagination['current_page'] == $pagination['last_page'] ? 'disabled' : '' }}">
                                    <a class="page-link" 
                                       href="{{ route('admin.users.index', ['page' => $pagination['current_page'] + 1, 'search' => $search ?? '']) }}"
                                       aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        @elseif(isset($pagination) && (!isset($pagination['total']) || $pagination['total'] === 0))
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-info-circle"></i> No users to display
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>