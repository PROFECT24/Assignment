<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is authenticated and is admin
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        // Get the user ID from the route parameter
        $userId = $this->route('user');
        
        // If it's a User model instance, get the ID
        if (is_object($userId)) {
            $userId = $userId->id;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'role' => 'required|in:admin,manager,user',
        ];

        // Only require password for new users, make it optional for updates
        if ($this->isMethod('POST')) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'sometimes|nullable|string|min:8';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required for new users.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.required' => 'The role field is required.',
            'role.in' => 'The selected role is invalid.',
        ];
    }
}