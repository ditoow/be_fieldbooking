<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    /**
     * Send a reset password link / token to the user's email.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->email;
        $plainToken = Str::random(64);

        // Save or update the token in the password_reset_tokens table (hashed for security)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        // In development/local environment, we return the token and a mock reset link in the response
        // so that the developer/frontend can easily capture it without needing SMTP setup.
        return response()->json([
            'message' => 'Tautan reset password telah dikirim ke email Anda.',
            'reset_link' => 'http://localhost:3000/reset-password?token=' . $plainToken . '&email=' . urlencode($email),
            'token' => $plainToken,
        ]);
    }

    /**
     * Reset the user's password using the token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Permintaan reset password tidak ditemukan atau token tidak valid.'
            ], 422);
        }

        // Check if token is expired (older than 60 minutes)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Token reset password telah kedaluwarsa.'
            ], 422);
        }

        // Verify the token matches
        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Token reset password tidak valid.'
            ], 422);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->firstOrFail();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the token so it cannot be reused
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password Anda berhasil diperbarui! Silakan masuk kembali dengan password baru.'
        ]);
    }
}
