<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ScopeRole;
use App\Models\Subsidiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

class UserAccessEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Tests\Seeders\TestBaselineSeeder::class);
        Role::findOrCreate('company-member', 'api');
        Role::findOrCreate('subsidiary-member', 'api');
        Role::findOrCreate('super-admin', 'api');
    }

    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function graph(): array
    {
        $company = Company::create([
            'company_name' => 'ACME',
            'company_rut' => '76'.random_int(1000000,9999999).'-K',
            'contact_email' => 'x'.uniqid().'@ex.com',
        ]);
        $s1 = Subsidiary::create([
            'company_id'=>$company->id,
            'subsidiary_name'=>'S1',
            'subsidiary_rut' => '76'.random_int(1000000,9999999).'-K',
            'subsidiary_email' => 's1'.uniqid().'@example.com',
            'subsidiary_manager_name' => 'Mgr 1',
            'subsidiary_manager_email' => 'm1'.uniqid().'@example.com',
            'subsidiary_status' => true,
        ]);
        $s2 = Subsidiary::create([
            'company_id'=>$company->id,
            'subsidiary_name'=>'S2',
            'subsidiary_rut' => '77'.random_int(1000000,9999999).'-K',
            'subsidiary_email' => 's2'.uniqid().'@example.com',
            'subsidiary_manager_name' => 'Mgr 2',
            'subsidiary_manager_email' => 'm2'.uniqid().'@example.com',
            'subsidiary_status' => true,
        ]);
        $b1 = Branch::create([
            'subsidiary_id'=>$s1->id,
            'branch_name'=>'B1',
            'branch_address' => 'Addr 1',
            'branch_phone' => '2222222',
            'branch_email' => 'b1'.uniqid().'@example.com',
            'branch_status' => true,
            'branch_manager_name' => 'Mgr 1',
            'branch_manager_email' => 'mgr1'.uniqid().'@example.com',
        ]);
        $b2 = Branch::create([
            'subsidiary_id'=>$s2->id,
            'branch_name'=>'B2',
            'branch_address' => 'Addr 2',
            'branch_phone' => '3333333',
            'branch_email' => 'b2'.uniqid().'@example.com',
            'branch_status' => true,
            'branch_manager_name' => 'Mgr 2',
            'branch_manager_email' => 'mgr2'.uniqid().'@example.com',
        ]);
        return compact('company','s1','s2','b1','b2');
    }

    protected function superAdmin(): User
    {
        $u = User::create([
            'first_name'=>'Admin','last_name'=>'X','email'=>'a'.uniqid().'@ex.com','password'=>bcrypt('pass'),'rut'=>'1'.random_int(1000000,9999999).'-K'
        ]);
        // Normalize password hashing to configured driver
        $u->password = \Illuminate\Support\Facades\Hash::make('pass');
        $u->save();
        $u->assignRole('super-admin');
        return $u;
    }

    public function test_sync_subsidiaries_add_and_replace(): void
    {
        extract($this->graph());
        $actor = $this->superAdmin();
        $token = $this->tokenFor($actor);
        $target = User::create(['first_name'=>'U','last_name'=>'Y','email'=>'u'.uniqid().'@ex.com','password'=>\Illuminate\Support\Facades\Hash::make('pass'),'rut'=>'2'.random_int(1000000,9999999).'-K']);

        // add S1
        $resp = $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/subsidiaries", [ 'ids'=>[$s1->id], 'mode'=>'add' ]);
        $resp->assertStatus(200)->assertJsonPath('attached.0', $s1->id);

        $this->assertDatabaseHas('scope_roles', [
            'user_id'=>$target->id,
            'scope_type'=>'subsidiary',
            'scope_id'=>$s1->id,
        ]);

        // sync to [S2]
        $resp = $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/subsidiaries", [ 'ids'=>[$s2->id], 'mode'=>'sync' ]);
        $resp->assertStatus(200);

        $this->assertDatabaseMissing('scope_roles', [ 'user_id'=>$target->id,'scope_type'=>'subsidiary','scope_id'=>$s1->id ]);
        $this->assertDatabaseHas('scope_roles', [ 'user_id'=>$target->id,'scope_type'=>'subsidiary','scope_id'=>$s2->id ]);
    }

    public function test_sync_branches_add_and_replace(): void
    {
        extract($this->graph());
        $actor = $this->superAdmin();
        $token = $this->tokenFor($actor);
        $target = User::create(['first_name'=>'U2','last_name'=>'Y','email'=>'u2'.uniqid().'@ex.com','password'=>\Illuminate\Support\Facades\Hash::make('pass'),'rut'=>'3'.random_int(1000000,9999999).'-K']);

        // add B1
        $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/branches", [ 'ids'=>[$b1->id], 'mode'=>'add' ])
            ->assertStatus(200);
        $this->assertDatabaseHas('branch_user', [ 'user_id'=>$target->id,'branch_id'=>$b1->id ]);

        // sync to [B2]
        $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/branches", [ 'ids'=>[$b2->id], 'mode'=>'sync' ])
            ->assertStatus(200);
        $this->assertDatabaseMissing('branch_user', [ 'user_id'=>$target->id,'branch_id'=>$b1->id ]);
        $this->assertDatabaseHas('branch_user', [ 'user_id'=>$target->id,'branch_id'=>$b2->id ]);
    }

    public function test_sync_companies_add_and_replace(): void
    {
        $companyA = Company::create(['company_name'=>'A','company_rut'=>'76'.random_int(1000000,9999999).'-K','contact_email'=>'a'.uniqid().'@ex.com']);
        $companyB = Company::create(['company_name'=>'B','company_rut'=>'77'.random_int(1000000,9999999).'-K','contact_email'=>'b'.uniqid().'@ex.com']);
        $actor = $this->superAdmin();
        $token = $this->tokenFor($actor);
        $target = User::create(['first_name'=>'U3','last_name'=>'Z','email'=>'u3'.uniqid().'@ex.com','password'=>\Illuminate\Support\Facades\Hash::make('pass'),'rut'=>'4'.random_int(1000000,9999999).'-K']);

        // add A
        $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/companies", [ 'ids'=>[$companyA->id], 'mode'=>'add' ])
            ->assertStatus(200);

        $this->assertDatabaseHas('company_user', [ 'user_id'=>$target->id,'company_id'=>$companyA->id ]);
        $this->assertDatabaseHas('scope_roles', [ 'user_id'=>$target->id,'scope_type'=>'company','scope_id'=>$companyA->id ]);

        // sync to [B]
        $this->withHeader('Authorization','Bearer '.$token)
            ->postJson("/api/users/{$target->id}/access/companies", [ 'ids'=>[$companyB->id], 'mode'=>'sync' ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('company_user', [ 'user_id'=>$target->id,'company_id'=>$companyA->id ]);
        $this->assertDatabaseHas('company_user', [ 'user_id'=>$target->id,'company_id'=>$companyB->id ]);
        $this->assertDatabaseMissing('scope_roles', [ 'user_id'=>$target->id,'scope_type'=>'company','scope_id'=>$companyA->id ]);
        $this->assertDatabaseHas('scope_roles', [ 'user_id'=>$target->id,'scope_type'=>'company','scope_id'=>$companyB->id ]);
    }
}
