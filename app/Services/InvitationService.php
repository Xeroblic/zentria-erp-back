<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Models\{Invitation, User, Branch};
use App\Notifications\UserInvitationNotification;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * Create a branch-scoped invitation + temporary user (is_active = false).
     * $payload must include: email, first_name, last_name, role_name (+ optional fields).
     */
    public function invite(
        User $inviter,
        int $branchId,
        array $payload,
        ?int $ttlDays = 7
    ): Invitation {
        $branch = Branch::query()->with(['subsidiary.company'])->findOrFail($branchId);

        // RBAC guard (company/subsidiary/branch rules)
        if (! $this->canInviteToBranch($inviter, $branch)) {
            throw ValidationException::withMessages(['permission' => 'Not allowed to invite to this branch.']);
        }

        // Resolve role dynamically (prefer role_id; fallback to role_name with alias map)
        $role = $this->resolveRole($payload);

        // Email validation + uniqueness (block if ANY user already has this email)
        $email = strtolower(trim($payload['email'] ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'Invalid email.']);
        }
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email already registered.']);
        }

        return DB::transaction(function () use ($inviter, $branch, $email, $payload, $role, $ttlDays) {
            $token = (string) Str::uuid();
            $tempPasswordPlain = Str::random(16);
            $tempPasswordHash = Hash::make($tempPasswordPlain);
            $expiresAt = now()->addDays($ttlDays ?? 7);

            // 1) Create temporary user (is_active = false)
            $user = new User();
            $user->first_name       = $payload['first_name']       ?? null;
            $user->middle_name      = $payload['middle_name']      ?? null;
            $user->last_name        = $payload['last_name']        ?? null;
            $user->second_last_name = $payload['second_last_name'] ?? null;
            $user->position         = $payload['position']         ?? null;
            $user->rut              = $payload['rut']              ?? null;
            $user->phone_number     = $payload['phone_number']     ?? null;
            $user->address          = $payload['address']          ?? null;
            $user->email            = $email;
            $user->password         = $tempPasswordHash; // TEMP HASH (never plain)
            $user->is_active        = false;
            $user->primary_branch_id= $branch->id; // seed primary scope
            $user->save();

            // 2) Create invitation snapshot
            $inv = Invitation::create([
                'uid'               => (string) Str::ulid(),
                'token'             => $token,
                'email'             => $email,
                'first_name'        => $payload['first_name'] ?? '',
                'last_name'         => $payload['last_name'] ?? '',
                'rut'               => $payload['rut'] ?? null,
                'position'          => $payload['position'] ?? null,
                'phone_number'      => $payload['phone_number'] ?? null,
                'address'           => $payload['address'] ?? null,
                'invited_by'        => $inviter->id,
                'branch_id'         => $branch->id,
                'role_id'           => $role->id,
                'role_name'         => $role->name,
                'permissions'       => $payload['permissions'] ?? null,
                'temporary_password'=> $tempPasswordHash, // store HASH only
                'status'            => InvitationStatus::PENDING,
                'expires_at'        => $expiresAt,
                'sent_at'           => now(),
                'data'              => $payload['data'] ?? null,
            ]);

            $activationUrl = $this->buildActivationUrl($token);

            // etiqueta segura de sucursal (por si branch_name viene null)
            $branchLabel = (string) (
                $branch->branch_name
                ?? $branch->branch_manager_name
                ?? $branch->branch_email
                ?? ("Sucursal #{$branch->id}")
            );

            // Después (posicional: no depende de los nombres del constructor)
            Notification::route('mail', $email)->notify(
                new UserInvitationNotification(
                    $activationUrl,                 // activationUrl
                    $role->name,                    // role
                    $branchLabel,                   // branchName (puede ser null/str)
                    $expiresAt->toIso8601String()   // expiresAt (string)
                )
            );

            // Adjuntar contraseña temporal en claro solo para respuesta API (no persistente)
            $inv->setAttribute('temporary_password_plain', $tempPasswordPlain);
            return $inv;
        });
    }

    /** Activation: validates token, sets is_active=true, updates password, marks invite used. */
    public function activate(string $token, string $newPassword): array
    {
        $inv = Invitation::where('token', $token)->first();
        if (! $inv) throw ValidationException::withMessages(['token' => 'Invalid token.']);

        if ($inv->status !== InvitationStatus::PENDING) {
            throw ValidationException::withMessages(['token' => 'Invitation no longer valid.']);
        }
        if ($inv->expires_at->isPast()) {
            $inv->status = InvitationStatus::EXPIRED;
            $inv->save();
            throw ValidationException::withMessages(['token' => 'Invitation expired.']);
        }

        // User is the temp one we created on invite (no token column on users)
        $user = User::where('email', $inv->email)->first();
        if (! $user) throw ValidationException::withMessages(['token' => 'Activation user not found.']);

        // Finalize user account
        $user->password          = Hash::make($newPassword);
        $user->is_active         = true;
        $user->email_verified_at = now();

        // Optionally hydrate missing profile data from invitation
        $user->first_name        = $user->first_name       ?: $inv->first_name;
        $user->last_name         = $user->last_name        ?: $inv->last_name;
        $user->phone_number      = $user->phone_number     ?: $inv->phone_number;
        $user->address           = $user->address          ?: $inv->address;
        if (empty($user->primary_branch_id)) {
            $user->primary_branch_id = $inv->branch_id;
        }
        $user->save();

        // Attach to branch (pivot) and assign role/permissions
        if (method_exists($user, 'branches')) {
            $user->branches()->syncWithoutDetaching([$inv->branch_id]);
        }
        $this->assignScopedRoleAndPermissions($user, $inv);

        $inv->status = InvitationStatus::USED;
        $inv->accepted_at = now();
        $inv->save();

        return ['user_id' => $user->id, 'email' => $user->email];
    }

    public function canInviteToBranch(User $inviter, Branch $targetBranch): bool
    {
        if ($inviter->hasRole('super-admin')) return true;

        // company-admin (admin-empresa): same company
        if ($inviter->hasRole('company-admin') || $inviter->hasRole('admin-empresa')) {
            return $inviter->branches()->whereHas('subsidiary.company', function ($q) use ($targetBranch) {
                $q->where('companies.id', $targetBranch->subsidiary->company_id);
            })->exists();
        }

        // subsidiary-admin (jefe-subempresa): same subsidiary
        if ($inviter->hasRole('subsidiary-admin') || $inviter->hasRole('jefe-subempresa')) {
            return $inviter->branches()->where('subsidiary_id', $targetBranch->subsidiary_id)->exists();
        }

        // branch-admin (jefe-sucursal): same branch only
        if ($inviter->hasRole('branch-admin') || $inviter->hasRole('jefe-sucursal')) {
            return $inviter->branches()->whereKey($targetBranch->id)->exists();
        }

        return false;
    }

    private function resolveRole(array $payload): Role
    {
        // Prefer role_id if provided
        if (!empty($payload['role_id'])) {
            $role = Role::query()
                ->whereKey((int) $payload['role_id'])
                ->where('guard_name', 'api')
                ->first();
            if (! $role) {
                throw ValidationException::withMessages(['role_id' => 'Invalid role_id.']);
            }
            return $role;
        }

        // Fallback: role_name provided; allow Spanish aliases but validate against DB
        $input = (string) ($payload['role_name'] ?? '');
        $normalized = $this->mapSpanishAlias($input);

        $role = Role::query()
            ->where('name', $normalized)
            ->where('guard_name', 'api')
            ->first();
        if (! $role) {
            throw ValidationException::withMessages(['role' => 'Invalid role.']);
        }
        return $role;
    }

    private function mapSpanishAlias(string $roleName): string
    {
        $map = [
            'admin-empresa'   => 'company-admin',
            'jefe-subempresa' => 'subsidiary-admin',
            'jefe-sucursal'   => 'branch-admin',
        ];
        $key = strtolower(trim($roleName));
        return $map[$key] ?? trim($roleName);
    }

    private function assignScopedRoleAndPermissions(User $user, Invitation $inv): void
    {
        if (method_exists($user, 'assignRole')) {
            if (!empty($inv->role_id)) {
                // Assign via Role model if we have id
                $role = Role::find($inv->role_id);
                if ($role) {
                    $user->assignRole($role);
                } else {
                    $user->assignRole($inv->role_name);
                }
            } else {
                $user->assignRole($inv->role_name);
            }
        }
        // Example (if you use scope_roles):
        // app(ContextualRoleService::class)->assign($user, $inv->role_name, 'branch', $inv->branch_id);

        if (!empty($inv->permissions) && method_exists($user, 'syncPermissions')) {
            $user->syncPermissions($inv->permissions);
        }
    }

    private function buildActivationUrl(string $token): string
    {
        // FRONTEND_ACTIVATION_URL is treated as BASE ONLY.
        // Final format: {BASE}/usuarios/activar/{token}
        $base = config('app.frontend_activation_url');
        if ($base) {
            $base = rtrim($base, "/ ");
            return $base . '/usuarios/activar/' . $token;
        }

        // Backend fallback (renderizado por Laravel)
        return url('/usuarios/activar/'.$token);
    }
}
