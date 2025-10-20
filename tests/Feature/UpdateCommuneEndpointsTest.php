<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Subsidiary;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateCommuneEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function makeRegionProvinceCommune(int $regionId = 1, int $provinceId = 1, int $communeId = 1001): int
    {
        DB::table('regions')->insert([
            'id' => $regionId,
            'name' => 'Region Test',
            'ordinal' => 'I',
            'geographic_order' => 1,
        ]);
        DB::table('provinces')->insert([
            'id' => $provinceId,
            'name' => 'Provincia Test',
            'region_id' => $regionId,
        ]);
        DB::table('communes')->insert([
            'id' => $communeId,
            'name' => 'Comuna Test',
            'province_id' => $provinceId,
        ]);
        return $communeId;
    }

    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function superAdmin(): User
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin'.uniqid().'@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'rut' => (string) random_int(10000000, 19999999).'-K',
        ]);
        $user->assignRole('super-admin');
        return $user;
    }

    protected function regularUser(): User
    {
        return User::create([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => 'user'.uniqid().'@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'rut' => (string) random_int(20000000, 29999999).'-K',
        ]);
    }

    public function test_patch_company_commune_updates_value(): void
    {
        $communeId = $this->makeRegionProvinceCommune();
        $company = Company::create([
            'company_name' => 'CompTest',
            'company_rut' => '76'.random_int(1000000,9999999).'-K',
            'contact_email' => 'c'.uniqid().'@example.com',
        ]);
        $token = $this->tokenFor($this->superAdmin());

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/companies/{$company->id}/commune", ['commune_id' => $communeId]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.commune_id', $communeId);
    }

    public function test_patch_subsidiary_commune_updates_value(): void
    {
        $communeId = $this->makeRegionProvinceCommune(2, 2, 2001);
        $company = Company::create([
            'company_name' => 'CompTest2',
            'company_rut' => '77'.random_int(1000000,9999999).'-K',
            'contact_email' => 'c2'.uniqid().'@example.com',
        ]);
        $subsidiary = Subsidiary::create([
            'company_id' => $company->id,
            'subsidiary_name' => 'SubTest',
            'subsidiary_rut' => '76'.random_int(1000000,9999999).'-1',
            'subsidiary_website' => null,
            'subsidiary_phone' => '+56 2 1234 5678',
            'subsidiary_address' => 'Dir test',
            'commune_id' => null,
            'subsidiary_email' => 's'.uniqid().'@example.com',
            'subsidiary_created_at' => now(),
            'subsidiary_updated_at' => null,
            'subsidiary_manager_name' => 'Manager',
            'subsidiary_manager_phone' => '+56 9 1111 2222',
            'subsidiary_manager_email' => 'm'.uniqid().'@example.com',
            'subsidiary_status' => true,
        ]);
        $token = $this->tokenFor($this->superAdmin());

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/subsidiaries/{$subsidiary->id}/commune", ['commune_id' => $communeId]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.commune_id', $communeId);
    }

    public function test_patch_branch_commune_updates_value(): void
    {
        $communeId = $this->makeRegionProvinceCommune(3, 3, 3001);
        $company = Company::create([
            'company_name' => 'CompTest3',
            'company_rut' => '78'.random_int(1000000,9999999).'-K',
            'contact_email' => 'c3'.uniqid().'@example.com',
        ]);
        $subsidiary = Subsidiary::create([
            'company_id' => $company->id,
            'subsidiary_name' => 'SubTest3',
            'subsidiary_rut' => '79'.random_int(1000000,9999999).'-2',
            'subsidiary_email' => 's3'.uniqid().'@example.com',
            'subsidiary_manager_name' => 'Mgr3',
            'subsidiary_manager_email' => 'm3'.uniqid().'@example.com',
            'subsidiary_status' => true,
        ]);
        $branch = Branch::create([
            'subsidiary_id' => $subsidiary->id,
            'branch_name' => 'BranchTest',
            'branch_address' => 'Addr',
            'branch_phone' => '22223333',
            'branch_email' => 'b'.uniqid().'@example.com',
            'branch_status' => true,
            'branch_manager_name' => 'BM',
            'branch_manager_email' => 'bm'.uniqid().'@example.com',
        ]);
        $token = $this->tokenFor($this->superAdmin());

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/branches/{$branch->id}/commune", ['commune_id' => $communeId]);

        $resp->assertStatus(200)
            ->assertJsonPath('data.commune_id', $communeId);
    }

    public function test_patch_company_commune_invalid_id_returns_422(): void
    {
        // Create minimal company
        $company = Company::create([
            'company_name' => 'X',
            'company_rut' => '79'.random_int(1000000,9999999).'-K',
            'contact_email' => 'cx'.uniqid().'@example.com',
        ]);
        $token = $this->tokenFor($this->superAdmin());

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/companies/{$company->id}/commune", ['commune_id' => 999999]);

        $resp->assertStatus(422);
    }

    public function test_patch_company_commune_forbidden_without_auth(): void
    {
        $company = Company::create([
            'company_name' => 'Y',
            'company_rut' => '80'.random_int(1000000,9999999).'-K',
            'contact_email' => 'cy'.uniqid().'@example.com',
        ]);
        $resp = $this->patchJson("/api/companies/{$company->id}/commune", ['commune_id' => null]);
        $resp->assertStatus(401);
    }
}
