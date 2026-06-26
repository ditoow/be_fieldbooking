<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Create user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('old_password'),
            'phone' => '+6281234567890',
        ]);
        $this->user->assignRole('umum');
    }

    /**
     * Test requesting a reset link.
     */
    public function test_requesting_reset_link_creates_token_and_returns_response(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@test.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'reset_link', 'token']);
        
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@test.com',
        ]);
    }

    /**
     * Test resetting password with a valid token.
     */
    public function test_resetting_password_with_valid_token_succeeds(): void
    {
        // First, request the token
        $this->postJson('/api/forgot-password', [
            'email' => 'test@test.com',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', 'test@test.com')->first();
        $this->assertNotNull($record);

        // We can capture the plain token from the response, or we can mock/insert it directly.
        // Let's call the forgot password API and extract the token.
        $forgotResponse = $this->postJson('/api/forgot-password', [
            'email' => 'test@test.com',
        ]);
        $plainToken = $forgotResponse->json('token');

        // Reset the password
        $response = $this->postJson('/api/reset-password', [
            'token' => $plainToken,
            'email' => 'test@test.com',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Password Anda berhasil diperbarui! Silakan masuk kembali dengan password baru.'
        ]);

        // Verify password updated in DB
        $this->user->refresh();
        $this->assertTrue(Hash::check('new_password123', $this->user->password));

        // Verify token deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'test@test.com',
        ]);
    }

    /**
     * Test resetting password with an invalid token fails.
     */
    public function test_resetting_password_with_invalid_token_fails(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@test.com',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(422);
        // The token verification will fail because the database doesn't have a record or hash check fails
    }

    /**
     * Test resetting password with an expired token fails.
     */
    public function test_resetting_password_with_expired_token_fails(): void
    {
        // Create an expired token manually in DB (older than 60 minutes)
        DB::table('password_reset_tokens')->insert([
            'email' => 'test@test.com',
            'token' => Hash::make('expired-token'),
            'created_at' => Carbon::now()->subMinutes(61),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'expired-token',
            'email' => 'test@test.com',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Token reset password telah kedaluwarsa.'
        ]);
    }
}
