<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;

class CompanyUserController extends Controller
{
    public function index(Company $company)
    {
        $this->authorize('view', $company);

        $users = User::byCompany($company->id)
            ->with(['branches.subsidiary.company', 'primaryBranch'])
            ->get()
            ->map(function (User $u) {
                $primary = $u->primaryBranch;
                return [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'email' => $u->email,
                    'position' => $u->position,
                    'rut' => $u->rut,
                    'phone_number' => $u->phone_number,
                    'is_active' => $u->is_active,
                    'primary_branch' => $primary ? [
                        'id' => $primary->id,
                        'name' => $primary->branch_name,
                    ] : null,
                    'memberships' => $u->branches->map(fn($b) => [
                        'id' => $b->id,
                        'name' => $b->branch_name,
                        'subsidiary_id' => $b->subsidiary_id,
                        'company_id' => $b->subsidiary->company_id,
                        'is_primary' => (bool) $b->pivot->is_primary,
                        'position' => $b->pivot->position,
                    ])->values(),
                ];
            });

        return response()->json(['usuarios' => $users]);
    }
}
