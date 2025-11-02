<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100); // Limit per_page to 100 max
            $page = max($request->get('page', 1), 1); // Ensure page is at least 1

            Log::info('Admin users index called', [
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role,
                'page' => $page,
                'per_page' => $perPage,
                'search' => $request->get('search')
            ]);

            $usersQuery = User::query()
                ->when($request->has('search'), function ($query) use ($request) {
                    $search = $request->get('search');
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('role', 'like', "%{$search}%");
                })
                ->orderBy('id', 'asc'); // Order by ID ascending to show from ID 1

            $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);

            // Validate and correct page number if out of bounds
            if ($page > $users->lastPage() && $users->lastPage() > 0) {
                $users = $usersQuery->paginate($perPage, ['*'], 'page', $users->lastPage());
            }

            Log::info('Users fetched successfully', [
                'count' => $users->count(),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage()
            ]);

            return response()->json([
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in admin users index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        try {
            return response()->json(new UserResource($user));
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function store(AdminUserRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
            ]);

            Log::info('User created successfully', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'User created successfully',
                'user' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create user'], 500);
        }
    }

public function update(AdminUserRequest $request, User $user): JsonResponse
{
    try {
        \Log::info('=== USER UPDATE START ===');
        \Log::info('Updating user ID: ' . $user->id);
        \Log::info('Request data:', $request->all());
        \Log::info('Authenticated user:', [
            'id' => auth()->id(),
            'role' => auth()->user()->role
        ]);

        // Check if user has admin permission
        if (!auth()->user()->isAdmin()) {
            \Log::error('User does not have admin permissions', [
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role
            ]);
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $data = $request->validated();
        \Log::info('Validated data:', $data);
        
        // Handle password update only if provided and not empty
        if (isset($data['password']) && !empty(trim($data['password']))) {
            $data['password'] = bcrypt($data['password']);
            \Log::info('Password will be updated');
        } else {
            // Remove password from data if not provided or empty
            unset($data['password']);
            \Log::info('Password field removed from update data');
        }

        \Log::info('Final data to update:', $data);

        // Update user
        $user->update($data);

        \Log::info('User updated successfully', [
            'user_id' => $user->id,
            'updated_fields' => array_keys($data)
        ]);

        \Log::info('=== USER UPDATE SUCCESS ===');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($user),
        ]);

    } catch (\Exception $e) {
        \Log::error('=== USER UPDATE FAILED ===');
        \Log::error('Error updating user: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to update user',
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function destroy(User $user): JsonResponse
    {
        try {
            if ($user->id === auth()->id()) {
                return response()->json(['error' => 'You cannot delete your own account'], 422);
            }

            $user->delete();

            Log::info('User deleted successfully', ['user_id' => $user->id]);

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete user'], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('q');
            $perPage = min($request->get('per_page', 15), 100);
            $page = max($request->get('page', 1), 1);

            $usersQuery = User::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%")
                ->orderBy('id', 'asc');

            $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);

            // Validate and correct page number if out of bounds
            if ($page > $users->lastPage() && $users->lastPage() > 0) {
                $users = $usersQuery->paginate($perPage, ['*'], 'page', $users->lastPage());
            }

            Log::info('User search completed', [
                'search_term' => $search,
                'results_count' => $users->count(),
                'current_page' => $users->currentPage()
            ]);

            return response()->json([
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in user search: ' . $e->getMessage());
            return response()->json(['error' => 'Search failed'], 500);
        }
    }
}