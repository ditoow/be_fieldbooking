<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function indexUsers(Request $request)
    {
        $query = User::with('roles');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_number', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->query('per_page', 10);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return UserResource::collection($users);
    }

    public function updateUserStatus(UpdateUserStatusRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Cannot change status of another Admin account.'
            ], 403);
        }

        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'User status updated to ' . $request->status,
            'user' => $user
        ]);
    }
}
