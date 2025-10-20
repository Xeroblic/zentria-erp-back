<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserInvitationController extends Controller
{
    public function __construct(private InvitationService $service) {}

    /** POST /api/user/invite */
    public function invite(Request $request)
    {
        // Use 'permission:invite-users' middleware in routes; optional double-check here.
        // $this->authorize('invite', auth()->user());

        $data = $request->validate([
            'branch_id'         => ['required','integer','exists:branches,id'],
            'email'             => ['required','email:rfc'], // en prod puedes usar 'email:rfc,dns'
            'first_name'        => ['required','string','max:255'],
            'middle_name'       => ['nullable','string','max:255'],
            'last_name'         => ['required','string','max:255'],
            'second_last_name'  => ['nullable','string','max:255'],
            'position'          => ['nullable','string','max:255'],
            'rut'               => ['nullable','string','max:255'],
            'phone_number'      => ['nullable','string','max:255'],
            'address'           => ['nullable','string'],
            'role_name'         => ['required','string','max:255'],
            'permissions'       => ['nullable','array'],
            'data'              => ['nullable','array'],
            'ttl_days'          => ['nullable','integer','min:1','max:60'],
        ]);

        $inv = $this->service->invite(
            inviter: $request->user(),
            branchId: (int) $data['branch_id'],
            payload: $data,
            ttlDays: $data['ttl_days'] ?? 7,
        );

        return response()->json([
            'id'         => $inv->id,
            'uid'        => $inv->uid,
            'email'      => $inv->email,
            'status'     => $inv->status,
            'expires_at' => $inv->expires_at,
            // Solo para pruebas: contraseña temporal en claro
            'temporary_password' => $inv->getAttribute('temporary_password_plain')
        ], 201);
    }

    /** GET /usuarios/activar/{token} — front prefill */
    public function showActivation(string $token)
    {
        $inv = Invitation::where('token', $token)->first();
        if (! $inv) return response()->json(['message' => 'Invalid token'], 404);

        if ($inv->status === InvitationStatus::PENDING && $inv->expires_at && $inv->expires_at->isPast()) {
            $inv->status = InvitationStatus::EXPIRED;
            $inv->save();
        }
        if ($inv->status !== InvitationStatus::PENDING) {
            return response()->json(['message' => 'Invitation no longer valid', 'status' => (string)$inv->status], 410);
        }

        return response()->json([
            'email'      => $inv->email,
            'first_name' => $inv->first_name,
            'last_name'  => $inv->last_name,
            'role_name'  => $inv->role_name,
            'branch_id'  => $inv->branch_id,
        ]);
    }

    /** POST /api/usuarios/activar — finalize password + returns JWT */
    public function activate(Request $request)
    {
        $data = $request->validate([
            'token'                 => ['required','uuid'],
            'password'              => ['required','string','min:8','confirmed'],
        ]);

        $result = $this->service->activate($data['token'], $data['password']);

        $user = auth()->getProvider()->retrieveById($result['user_id']);
        $jwt  = JWTAuth::fromUser($user);

        return response()->json([
            'token'      => $jwt,
            'token_type' => 'bearer',
            'user'       => [
                'id'         => $user->id,
                'email'      => $user->email,
                'is_active'  => $user->is_active,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
            ],
        ], 200);
    }

    /** GET /api/user/invitations — listar invitaciones con filtro opcional por estado */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable','in:pending,used,expired'],
        ]);

        $user = $request->user();

        $query = Invitation::query()
            ->with(['inviter:id,first_name,last_name,email']);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!method_exists($user, 'hasRole') || !$user->hasRole('super-admin')) {
            if (method_exists($user, 'branches')) {
                $query->whereIn('branch_id', function ($q) use ($user) {
                    $q->select('branches.id')
                        ->from('branches')
                        ->join('branch_user', 'branches.id', '=', 'branch_user.branch_id')
                        ->where('branch_user.user_id', $user->id);
                });
            } else {
                $query->where('invited_by', $user->id);
            }
        }

        $invitations = $query->orderByDesc('created_at')->get();

        $data = $invitations->map(function ($inv) {
            $invitedByName = $inv->inviter ? trim(($inv->inviter->first_name ?? '').' '.($inv->inviter->last_name ?? '')) : null;
            return [
                'id'           => $inv->id,
                'email'        => $inv->email,
                'role'         => $inv->role_name,
                'status'       => $inv->status,
                'invited_at'   => $inv->created_at,
                'expires_at'   => $inv->expires_at,
                'invited_by'   => $invitedByName ?: ($inv->inviter->email ?? null),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
