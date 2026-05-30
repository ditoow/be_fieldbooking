<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
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

   
    public function register(Request $request)
    {
  
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);
        
        $this->syncRoleByEmailSuffix($user);

        return response()->json([
            'message' => 'User berhasil didaftarkan!',
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
