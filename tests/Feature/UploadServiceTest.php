<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Field;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Database\Seeders\RoleSeeder;

class UploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    protected function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => "Bearer $token"];
    }

    public function test_admin_can_upload_field_photo_successfully(): void
    {
        Http::fake([
            'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/Field-Image/*' => Http::response(['message' => 'Success'], 201),
        ]);

        $admin = $this->createUser('admin');
        $image = UploadedFile::fake()->image('lapangan_voli.png');

        $response = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/upload/foto', [
                'foto' => $image,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'url']);
        $this->assertStringContainsString('/storage/v1/object/public/Field-Image/lapangan/', $response->json('url'));
    }

    public function test_admin_upload_photo_validation(): void
    {
        $admin = $this->createUser('admin');

        // Test missing file
        $response = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/upload/foto', []);
        $response->assertStatus(422);

        // Test oversized file (> 5MB)
        $largeImage = UploadedFile::fake()->create('heavy.png', 6000, 'image/png');
        $response = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/upload/foto', [
                'foto' => $largeImage,
            ]);
        $response->assertStatus(422);
    }

    public function test_user_can_upload_pdf_document_successfully(): void
    {
        Http::fake([
            'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/File-Document/*' => Http::response(['message' => 'Success'], 201),
        ]);

        $user = $this->createUser('umum');
        $pdf = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');

        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/upload/dokumen', [
                'dokumen' => $pdf,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'url']);
        $this->assertStringContainsString('/storage/v1/object/public/File-Document/dokumen/', $response->json('url'));
    }

    public function test_user_upload_document_validation(): void
    {
        $user = $this->createUser('umum');

        // Test wrong extension (e.g. image instead of pdf)
        $image = UploadedFile::fake()->image('image.png');
        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/upload/dokumen', [
                'dokumen' => $image,
            ]);
        $response->assertStatus(422);

        // Test oversized PDF (> 1MB)
        $largePdf = UploadedFile::fake()->create('heavy.pdf', 2000, 'application/pdf');
        $response = $this->withHeaders($this->authHeaders($user))
            ->postJson('/api/upload/dokumen', [
                'dokumen' => $largePdf,
            ]);
        $response->assertStatus(422);
    }

    public function test_student_can_upload_requirement_file_directly_as_pdf(): void
    {
        Http::fake([
            'https://qcizbglhafqgrphobbly.supabase.co/storage/v1/object/File-Document/*' => Http::response(['message' => 'Success'], 201),
        ]);

        $student = $this->createUser('mahasiswa');
        $field = Field::create([
            'name' => 'Lapangan Test',
            'category' => 'Futsal',
        ]);
        $field->detail()->create([
            'description' => 'Test',
            'surface_type' => 'vinyl',
            'rating' => 4.5,
            'status' => 'available',
        ]);

        $schedule = Schedule::create([
            'field_id' => $field->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'price' => 40000,
            'status' => 'booked',
        ]);

        $booking = Booking::create([
            'user_id' => $student->id,
            'status' => 'pending',
            'booking_type' => 'requirement',
            'expires_at' => now()->addMinutes(10),
            'total_price' => 40000,
        ]);
        $booking->schedules()->attach($schedule->id);

        $document = UploadedFile::fake()->create('persyaratan.pdf', 500, 'application/pdf');

        $response = $this->withHeaders($this->authHeaders($student))
            ->postJson("/api/bookings/{$booking->id}/upload", [
                'file' => $document,
            ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.file_url'));
        $this->assertStringContainsString('/storage/v1/object/public/File-Document/dokumen/', $response->json('data.file_url'));
    }
}
