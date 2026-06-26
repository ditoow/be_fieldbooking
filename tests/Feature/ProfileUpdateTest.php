<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $mahasiswaUser;
    private User $umumUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Create users
        $this->mahasiswaUser = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa@mhs.dinus.ac.id',
            'password' => bcrypt('password'),
            'phone' => '+6281234567890',
            'student_id' => 'A11.2023.00001',
        ]);
        $this->mahasiswaUser->assignRole('mahasiswa');

        $this->umumUser = User::create([
            'name' => 'Umum Test',
            'email' => 'umum@test.com',
            'password' => bcrypt('password'),
            'phone' => '+6281234567891',
        ]);
        $this->umumUser->assignRole('umum');
    }

    /**
     * Test that umum user cannot update email to end with @mhs.dinus.ac.id.
     */
    public function test_umum_user_cannot_update_email_to_student_domain(): void
    {
        $response = $this->actingAs($this->umumUser, 'api')
            ->postJson('/api/user/profile', [
                'name' => 'Umum Test Updated',
                'email' => 'hack@mhs.dinus.ac.id', // trying to use student email
                'phone' => '+6281234567891',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertEquals(
            'Email pengguna umum tidak boleh menggunakan domain mahasiswa.',
            $response->json('errors.email.0')
        );
    }

    /**
     * Test that mahasiswa user cannot update email to a non-student address.
     */
    public function test_mahasiswa_user_cannot_update_email_to_general_domain(): void
    {
        $response = $this->actingAs($this->mahasiswaUser, 'api')
            ->postJson('/api/user/profile', [
                'name' => 'Mahasiswa Test Updated',
                'email' => 'student_updated@gmail.com', // trying to use general email
                'phone' => '+6281234567890',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertEquals(
            'Email mahasiswa harus menggunakan domain @mhs.dinus.ac.id.',
            $response->json('errors.email.0')
        );
    }

    /**
     * Test that a successful profile update does not alter the user's role.
     */
    public function test_profile_update_does_not_change_role(): void
    {
        $response = $this->actingAs($this->umumUser, 'api')
            ->postJson('/api/user/profile', [
                'name' => 'Umum Test Updated',
                'email' => 'umum_new@test.com',
                'phone' => '+6281234567891',
            ]);

        $response->assertStatus(200);
        $this->umumUser->refresh();
        $this->assertTrue($this->umumUser->hasRole('umum'));
        $this->assertFalse($this->umumUser->hasRole('mahasiswa'));
    }

    /**
     * Test that registering as mahasiswa requires student_id.
     */
    public function test_registering_as_mahasiswa_requires_student_id(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Student',
            'email' => 'new_student@mhs.dinus.ac.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+6281234567895',
            'student_id' => '', // empty
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
        $this->assertEquals(
            'NIM wajib diisi untuk pendaftaran mahasiswa.',
            $response->json('errors.student_id.0')
        );
    }

    /**
     * Test that registering as umum forbids student_id.
     */
    public function test_registering_as_umum_forbids_student_id(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New General',
            'email' => 'new_general@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+6281234567895',
            'student_id' => 'A11.2023.00002', // passing student_id for general email
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
        $this->assertEquals(
            'NIM hanya boleh diisi oleh pengguna mahasiswa dengan email @mhs.dinus.ac.id.',
            $response->json('errors.student_id.0')
        );
    }
}
