<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Migraciones + seed de roles/permisos
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function makeUser(array $overrides = []): User
    {
        $defaults = [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'user'.uniqid().'@example.com',
            'password'   => 'password',
            'rut'        => '1'.random_int(1000000, 9999999).'-K',
        ];
        $payload = array_merge($defaults, $overrides);
        if (isset($payload['password']) && !str_starts_with($payload['password'], '$2y$')) {
            $payload['password'] = \Illuminate\Support\Facades\Hash::make($payload['password']);
        }
        return User::create($payload);
    }

    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/auth/users');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = $this->makeUser();
        $token = $this->tokenFor($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/users');

        $response->assertStatus(403);
    }

    public function test_user_with_permission_gets_200(): void
    {
        $user = $this->makeUser();
        // Dar permiso mínimo requerido
        $user->givePermissionTo('view-user');
        $token = $this->tokenFor($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/users');

        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_anything(): void
    {
        $user = $this->makeUser(['email' => 'admin'.uniqid().'@example.com']);
        $user->assignRole('super-admin');
        $token = $this->tokenFor($user);

        // Ruta que requiere ver usuarios
        $resp1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/users');
        $resp1->assertStatus(200);
    }
}
