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

class HierarchyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Tests\Seeders\TestBaselineSeeder::class);
        // Asegurar roles de acceso
        Role::findOrCreate('company-member', 'api');
        Role::findOrCreate('subsidiary-member', 'api');
        Role::findOrCreate('super-admin', 'api');
    }

    protected function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function makeGraph(): array
    {
        $company = Company::create([
            'company_name' => 'Comp',
            'company_rut' => '76'.random_int(1000000,9999999).'-K',
            'contact_email' => 'c'.uniqid().'@example.com',
        ]);
        $s1 = Subsidiary::create([
            'company_id'=>$company->id,
            'subsidiary_name'=>'S1',
            'subsidiary_rut' => '76'.random_int(1000000,9999999).'-K',
            'subsidiary_email' => 's1'.uniqid().'@example.com',
            'subsidiary_manager_name' => 'Manager 1',
            'subsidiary_manager_email' => 'm1'.uniqid().'@example.com',
            'subsidiary_status' => true,
        ]);
        $s2 = Subsidiary::create([
            'company_id'=>$company->id,
            'subsidiary_name'=>'S2',
            'subsidiary_rut' => '77'.random_int(1000000,9999999).'-K',
            'subsidiary_email' => 's2'.uniqid().'@example.com',
            'subsidiary_manager_name' => 'Manager 2',
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

    protected function makeUser(array $overrides=[]): User
    {
        $defaults = [
            'first_name'=>'U','last_name'=>'T','email'=>'u'.uniqid().'@ex.com','password'=>bcrypt('pass'),'rut'=>'1'.random_int(1000000,9999999).'-K'
        ];
        return User::create($defaults + $overrides);
    }

    public function test_company_member_sees_all_children(): void
    {
        extract($this->makeGraph());
        $u = $this->makeUser();

        ScopeRole::assignContextRole($u->id, 'company-member', 'company', $company->id);

        $subs = Subsidiary::visibleTo($u)->pluck('id')->sort()->values();
        $branches = Branch::visibleTo($u)->pluck('id')->sort()->values();

        $this->assertEquals([$s1->id, $s2->id], $subs->all());
        $this->assertEquals([$b1->id, $b2->id], $branches->all());
    }

    public function test_subsidiary_member_sees_only_its_branch_and_subsidiary(): void
    {
        extract($this->makeGraph());
        $u = $this->makeUser();

        ScopeRole::assignContextRole($u->id, 'subsidiary-member', 'subsidiary', $s1->id);

        $subs = Subsidiary::visibleTo($u)->pluck('id')->all();
        $branches = Branch::visibleTo($u)->pluck('id')->all();

        $this->assertContains($s1->id, $subs);
        $this->assertNotContains($s2->id, $subs);
        $this->assertContains($b1->id, $branches);
        $this->assertNotContains($b2->id, $branches);
    }

    public function test_direct_branch_access_grants_visibility_of_branch_and_parent_subsidiary(): void
    {
        extract($this->makeGraph());
        $u = $this->makeUser();
        $u->branches()->syncWithoutDetaching([$b2->id => ['is_primary'=>false]]);

        $subs = Subsidiary::visibleTo($u)->pluck('id')->all();
        $branches = Branch::visibleTo($u)->pluck('id')->all();

        $this->assertContains($s2->id, $subs);
        $this->assertContains($b2->id, $branches);
        $this->assertNotContains($b1->id, $branches);
    }

    public function test_policy_forbids_show_on_invisible_subsidiary(): void
    {
        extract($this->makeGraph());
        $u = $this->makeUser();
        $token = $this->tokenFor($u);

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/subsidiaries/{$s1->id}");
        $resp->assertStatus(403);
    }
}
