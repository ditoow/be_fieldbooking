<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if ($user && $user->isSuspended()) {
            return response()->json(['message' => 'Akun Anda telah ditangguhkan.'], 403);
        }

        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::guard('api')->user()->load('roles');
        return response()->json([
            'token' => $token,
            'user' => $user
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
        $user = DB::transaction(function () use ($validated) {
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

            return $user;
        });

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

    public function updateProfile(\App\Http\Requests\Auth\UpdateProfileRequest $request)
    {
        $user = Auth::guard('api')->user();

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $user->load('roles');

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user
        ]);
    }

    public function updatePassword(\App\Http\Requests\Auth\UpdatePasswordRequest $request)
    {
        $user = Auth::guard('api')->user();

        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password does not match our records.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Password updated successfully!'
        ]);
    }
}
