<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Field;
use App\Models\Schedule;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $mahasiswa;
    protected $field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
        ]);
        $this->admin->assignRole('admin');

        $this->mahasiswa = User::create([
            'name' => 'Mahasiswa User',
            'email' => 'mhs@mhs.com',
            'password' => bcrypt('password'),
            'phone' => '081234567891',
        ]);
        $this->mahasiswa->assignRole('mahasiswa');

        $this->field = Field::create([
            'name' => 'Lapangan Futsal A',
            'description' => 'Standard internasional',
            'surface_type' => 'vinyl',
            'category' => 'Futsal',
            'status' => 'available',
        ]);

        Schedule::create([
            'field_id' => $this->field->id,
            'date' => '2026-10-24',
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'price' => 100000,
            'status' => 'available',
        ]);
    }

    public function test_guest_cannot_update_or_delete_field()
    {
        $response1 = $this->patchJson("/api/admin/fields/{$this->field->id}", [
            'name' => 'Updated Name'
        ]);
        $response1->assertStatus(401);

        $response2 = $this->deleteJson("/api/admin/fields/{$this->field->id}");
        $response2->assertStatus(401);
    }

    public function test_non_admin_cannot_update_or_delete_field()
    {
        $response1 = $this->actingAs($this->mahasiswa, 'api')
            ->patchJson("/api/admin/fields/{$this->field->id}", [
                'name' => 'Updated Name'
            ]);
        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->mahasiswa, 'api')
            ->deleteJson("/api/admin/fields/{$this->field->id}");
        $response2->assertStatus(403);
    }

    public function test_admin_can_update_field()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/fields/{$this->field->id}", [
                'name' => 'Lapangan Baru Futsal',
                'status' => 'maintenance',
                'surface_type' => 'parket',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Lapangan Baru Futsal')
            ->assertJsonPath('data.status', 'maintenance')
            ->assertJsonPath('data.surface_type', 'parket');

        $this->assertDatabaseHas('fields', [
            'id' => $this->field->id,
            'name' => 'Lapangan Baru Futsal',
            'status' => 'maintenance',
            'surface_type' => 'parket',
        ]);
    }

    public function test_admin_can_delete_field_and_cascade_schedules()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/fields/{$this->field->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fields', ['id' => $this->field->id]);
        $this->assertDatabaseMissing('schedules', ['field_id' => $this->field->id]);
    }
}
