<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $this->syncRoleByEmailSuffix($user);
        }

        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => Auth::guard('api')->user()
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

   
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'student_id' => $validated['student_id'] ?? null,
        ]);
        
        $this->syncRoleByEmailSuffix($user);

        $role = $user->getRoleNames()->first();
        $userNumber = User::generateUserNumber($role);
        if ($userNumber) {
            $user->update(['user_number' => $userNumber]);
        }

        \App\Models\ActivityLog::create([
            'type' => 'info',
            'title' => 'New User',
            'description' => "New user {$user->name} ({$user->email}) has registered in the system.",
            'user_name' => $user->name,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user
        ], 201);
    }

    protected function syncRoleByEmailSuffix(User $user): void
    {
        
        if ($user->hasRole('admin')) {
            return;
        }

        if (str_ends_with($user->email, '@mhs.dinus.ac.id')) {
            $user->syncRoles(['mahasiswa']);
        } else {
            $user->syncRoles(['umum']);
        }
    }
}
