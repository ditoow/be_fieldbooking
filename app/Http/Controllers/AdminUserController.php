<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
 
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $user = User::findOrFail($id);
        
        
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Tidak dapat mengubah status akun sesama Admin.'
            ], 403);
        }

        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status user berhasil diperbarui menjadi ' . $request->status,
            'user' => $user
        ]);
    }
}
