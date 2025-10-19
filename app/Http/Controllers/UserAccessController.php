<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ScopeRole;
use App\Models\Subsidiary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    public function syncSubsidiaries(Request $request, User $user)
    {
        $actor = Auth::user();
        if (!$actor || (!$actor->hasRole('super-admin') && !$actor->can('edit-users'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'ids' => 'array',
            'ids.*' => 'integer|exists:subsidiaries,id',
            'mode' => 'required|string|in:sync,add,remove',
        ]);

        $ids = collect($data['ids'] ?? [])->unique()->values();
        $mode = $data['mode'];

        $subs = Subsidiary::whereIn('id', $ids)->get(['id','company_id']);

        // Filtrar por lo que el actor puede otorgar (company-admin del padre o subsidiary-admin del mismo id)
        $grantable = $subs->filter(function ($s) use ($actor) {
            return $actor->hasRole('super-admin')
                || $actor->hasContextRole('company-admin', 'company', $s->company_id)
                || $actor->hasContextRole('subsidiary-admin', 'subsidiary', $s->id);
        })->pluck('id')->all();

        // Role para acceso (no admin)
        $memberRole = Role::firstOrCreate(['name' => 'subsidiary-member', 'guard_name' => 'api']);

        $current = ScopeRole::query()
            ->forUser($user->id)
            ->where('scope_type', 'subsidiary')
            ->where('role_id', $memberRole->id)
            ->pluck('scope_id')
            ->all();

        $attached = [];
        $detached = [];
        $forbidden = array_values(array_diff($ids->all(), $grantable));

        DB::transaction(function () use ($mode, $grantable, $user, $memberRole, &$attached, &$detached, $current) {
            if ($mode === 'add') {
                foreach ($grantable as $sid) {
                    ScopeRole::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id' => $memberRole->id,
                        'scope_type' => 'subsidiary',
                        'scope_id' => $sid,
                    ]);
                    $attached[] = $sid;
                }
            } elseif ($mode === 'remove') {
                ScopeRole::query()
                    ->forUser($user->id)
                    ->where('scope_type', 'subsidiary')
                    ->where('role_id', $memberRole->id)
                    ->whereIn('scope_id', $grantable)
                    ->delete();
                $detached = $grantable;
            } elseif ($mode === 'sync') {
                $desired = $grantable;
                $toAttach = array_values(array_diff($desired, $current));
                $toDetach = array_values(array_diff($current, $desired));

                foreach ($toAttach as $sid) {
                    ScopeRole::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id' => $memberRole->id,
                        'scope_type' => 'subsidiary',
                        'scope_id' => $sid,
                    ]);
                }
                if (!empty($toDetach)) {
                    ScopeRole::query()
                        ->forUser($user->id)
                        ->where('scope_type', 'subsidiary')
                        ->where('role_id', $memberRole->id)
                        ->whereIn('scope_id', $toDetach)
                        ->delete();
                }
                $attached = $toAttach;
                $detached = $toDetach;
            }
        });

        return response()->json([
            'attached' => $attached,
            'detached' => $detached,
            'skipped' => [
                'forbidden' => $forbidden,
            ],
        ]);
    }

    public function syncBranches(Request $request, User $user)
    {
        $actor = Auth::user();
        if (!$actor || (!$actor->hasRole('super-admin') && !$actor->can('edit-users'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'ids' => 'array',
            'ids.*' => 'integer|exists:branches,id',
            'mode' => 'required|string|in:sync,add,remove',
        ]);

        $ids = collect($data['ids'] ?? [])->unique()->values();
        $mode = $data['mode'];

        $branches = Branch::whereIn('id', $ids)->with('subsidiary:id,company_id')->get(['id','subsidiary_id']);
        $grantable = $branches->filter(function ($b) use ($actor) {
            return $actor->hasRole('super-admin')
                || $actor->hasContextRole('company-admin', 'company', $b->subsidiary->company_id)
                || $actor->hasContextRole('subsidiary-admin', 'subsidiary', $b->subsidiary_id);
        })->pluck('id')->all();

        $forbidden = array_values(array_diff($ids->all(), $grantable));

        $current = $user->branches()->pluck('branches.id')->all();

        $attached = [];
        $detached = [];

        DB::transaction(function () use ($mode, $user, $grantable, $current, &$attached, &$detached) {
            if ($mode === 'add') {
                $payload = [];
                foreach ($grantable as $bid) { $payload[$bid] = ['is_primary' => false, 'position' => null]; }
                if (!empty($payload)) {
                    $user->branches()->syncWithoutDetaching($payload);
                    $attached = array_keys($payload);
                }
            } elseif ($mode === 'remove') {
                if (!empty($grantable)) {
                    $user->branches()->detach($grantable);
                    $detached = $grantable;
                }
            } elseif ($mode === 'sync') {
                $desired = $grantable;
                $toAttach = array_values(array_diff($desired, $current));
                $toDetach = array_values(array_diff($current, $desired));

                $payload = [];
                foreach ($toAttach as $bid) { $payload[$bid] = ['is_primary' => false, 'position' => null]; }
                if (!empty($payload)) {
                    $user->branches()->syncWithoutDetaching($payload);
                }
                if (!empty($toDetach)) {
                    $user->branches()->detach($toDetach);
                }
                $attached = $toAttach;
                $detached = $toDetach;
            }
        });

        return response()->json([
            'attached' => $attached,
            'detached' => $detached,
            'skipped' => [
                'forbidden' => $forbidden,
            ],
        ]);
    }

    public function syncCompanies(Request $request, User $user)
    {
        $actor = Auth::user();
        if (!$actor || (!$actor->hasRole('super-admin') && !$actor->can('edit-users'))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'ids' => 'array',
            'ids.*' => 'integer|exists:companies,id',
            'mode' => 'required|string|in:sync,add,remove',
        ]);

        $ids = collect($data['ids'] ?? [])->unique()->values();
        $mode = $data['mode'];

        $companies = Company::whereIn('id', $ids)->get(['id']);
        // Grantable: super-admin o company-admin de esa company
        $grantable = $companies->filter(function ($c) use ($actor) {
            return $actor->hasRole('super-admin') || $actor->hasContextRole('company-admin', 'company', $c->id);
        })->pluck('id')->all();

        $forbidden = array_values(array_diff($ids->all(), $grantable));

        $memberRole = Role::firstOrCreate(['name' => 'company-member', 'guard_name' => 'api']);

        $current = $user->companies()->pluck('companies.id')->all();

        $attached = [];
        $detached = [];

        DB::transaction(function () use ($mode, $user, $memberRole, $grantable, $current, &$attached, &$detached) {
            if ($mode === 'add') {
                $pivot = [];
                foreach ($grantable as $cid) {
                    $pivot[$cid] = [
                        'is_primary' => false,
                        'position_in_company' => null,
                        'joined_at' => now(),
                    ];
                    ScopeRole::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id' => $memberRole->id,
                        'scope_type' => 'company',
                        'scope_id' => $cid,
                    ]);
                }
                if (!empty($pivot)) {
                    $user->companies()->syncWithoutDetaching($pivot);
                }
                $attached = array_keys($pivot);
            } elseif ($mode === 'remove') {
                if (!empty($grantable)) {
                    $user->companies()->detach($grantable);
                    ScopeRole::query()
                        ->forUser($user->id)
                        ->where('scope_type', 'company')
                        ->where('role_id', $memberRole->id)
                        ->whereIn('scope_id', $grantable)
                        ->delete();
                    $detached = $grantable;
                }
            } elseif ($mode === 'sync') {
                $desired = $grantable;
                $toAttach = array_values(array_diff($desired, $current));
                $toDetach = array_values(array_diff($current, $desired));

                $pivot = [];
                foreach ($toAttach as $cid) {
                    $pivot[$cid] = [
                        'is_primary' => false,
                        'position_in_company' => null,
                        'joined_at' => now(),
                    ];
                    ScopeRole::firstOrCreate([
                        'user_id' => $user->id,
                        'role_id' => $memberRole->id,
                        'scope_type' => 'company',
                        'scope_id' => $cid,
                    ]);
                }
                if (!empty($pivot)) {
                    $user->companies()->syncWithoutDetaching($pivot);
                }
                if (!empty($toDetach)) {
                    $user->companies()->detach($toDetach);
                    ScopeRole::query()
                        ->forUser($user->id)
                        ->where('scope_type', 'company')
                        ->where('role_id', $memberRole->id)
                        ->whereIn('scope_id', $toDetach)
                        ->delete();
                }
                $attached = $toAttach;
                $detached = $toDetach;
            }
        });

        return response()->json([
            'attached' => $attached,
            'detached' => $detached,
            'skipped' => [
                'forbidden' => $forbidden,
            ],
        ]);
    }
}
