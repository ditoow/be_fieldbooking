<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Field;
use App\Models\DetailField;
use Database\Seeders\RoleSeeder;
use Database\Seeders\FieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FieldSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run RoleSeeder to setup roles and permissions
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

    public function test_field_seeder_populates_both_tables(): void
    {
        $this->seed(FieldSeeder::class);

        $this->assertEquals(5, Field::count());
        $this->assertEquals(5, DetailField::count());

        $field = Field::first();
        $this->assertNotNull($field->detail);
        $this->assertNotEmpty($field->description);
        $this->assertNotEmpty($field->surface_type);
        $this->assertNotNull($field->rating);
        $this->assertNotEmpty($field->status);
    }

    public function test_can_list_fields_publicly(): void
    {
        $this->seed(FieldSeeder::class);

        $response = $this->getJson('/api/fields');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'surface_type',
                    'rating',
                    'image_url',
                    'category',
                    'status',
                    'price_min',
                    'price_max'
                ]
            ]
        ]);
    }

    public function test_authenticated_user_can_view_field_details_with_schedules(): void
    {
        $this->seed(FieldSeeder::class);
        $user = $this->createUser('umum');
        $field = Field::first();

        $response = $this->withHeaders($this->authHeaders($user))
            ->getJson("/api/fields/{$field->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'surface_type',
                'rating',
                'image_url',
                'category',
                'status',
                'price_min',
                'price_max',
                'schedules'
            ]
        ]);
    }

    public function test_admin_can_create_field_with_details(): void
    {
        $admin = $this->createUser('admin');

        $payload = [
            'name' => 'Lapangan Baru',
            'description' => 'Ini deskripsi lapangan baru yang sangat keren.',
            'category' => 'Futsal',
            'status' => 'available',
            'surface_type' => 'vinyl',
        ];

        $response = $this->withHeaders($this->authHeaders($admin))
            ->postJson('/api/admin/fields', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Lapangan Baru');
        $response->assertJsonPath('data.description', 'Ini deskripsi lapangan baru yang sangat keren.');
        $response->assertJsonPath('data.surface_type', 'vinyl');
        $response->assertJsonPath('data.status', 'available');

        // Assert database
        $this->assertDatabaseHas('fields', [
            'name' => 'Lapangan Baru',
            'category' => 'Futsal'
        ]);

        $this->assertDatabaseHas('detail_fields', [
            'description' => 'Ini deskripsi lapangan baru yang sangat keren.',
            'surface_type' => 'vinyl',
            'status' => 'available'
        ]);
    }

    public function test_admin_can_update_field_and_details(): void
    {
        $this->seed(FieldSeeder::class);
        $admin = $this->createUser('admin');
        $field = Field::first();

        $payload = [
            'name' => 'Nama Lapangan Diubah',
            'description' => 'Deskripsi lapangan yang sudah diubah.',
            'status' => 'maintenance',
        ];

        $response = $this->withHeaders($this->authHeaders($admin))
            ->patchJson("/api/admin/fields/{$field->id}", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Nama Lapangan Diubah');
        $response->assertJsonPath('data.description', 'Deskripsi lapangan yang sudah diubah.');
        $response->assertJsonPath('data.status', 'maintenance');

        // Assert database has updated records
        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'name' => 'Nama Lapangan Diubah'
        ]);

        $this->assertDatabaseHas('detail_fields', [
            'field_id' => $field->id,
            'description' => 'Deskripsi lapangan yang sudah diubah.',
            'status' => 'maintenance'
        ]);
    }
}
