<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subsidiary;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitacionMail;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Autenticación JWT
    |--------------------------------------------------------------------------
    */

    public function register(Request $request)
    {
        $data = $request->only(['first_name', 'last_name', 'email', 'password']);

        $validator = Validator::make($data, [
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
        ]);

        $token = JWTAuth::fromUser($user);
        return response()->json(['token' => $token], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        return response()->json([
            'token' => $token,
            'user'  => JWTAuth::user()
        ]);
    }

    public function logout()
    {
        JWTAuth::logout(); // <- Solución JWT real
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function refresh()
    {
        return response()->json([
            'token' => JWTAuth::refresh()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Invitación y Activación de Usuarios
    |--------------------------------------------------------------------------
    */

    public function inviteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string',
            'last_name'        => 'required|string',
            'email'            => 'required|email|unique:users',
            'rut'              => 'required|string|unique:users',
            'position'         => 'required|string',
            'phone_number'     => 'nullable|string',
            'address'          => 'nullable|string',
            'branch_id'        => 'required|exists:branches,id',
            'role'             => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $tempPassword = Str::random(10);

        $user = User::create([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'email'        => $request->email,
            'password'     => Hash::make($tempPassword),
            'position'     => $request->position,
            'rut'          => $request->rut,
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
            'branch_id'    => $request->branch_id,
            'work_permits' => json_encode([]),
        ]);

        $user->assignRole($request->role);

        $invitationToken = Str::uuid()->toString();

        DB::table('password_resets')->insert([
            'email'      => $user->email,
            'token'      => $invitationToken,
            'created_at' => now(),
        ]);

        Mail::to($user->email)->send(new InvitacionMail($invitationToken, $tempPassword));

        return response()->json(['message' => 'Invitation sent'], 201);
    }

    public function activateAccount(Request $request, $token)
    {
        $record = DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if (!$record || \Carbon\Carbon::parse($record->created_at)->addHours(24)->isPast()) {
            return response()->json(['error' => 'Token inválido o expirado'], 400);
        }

        $user = User::where('email', $record->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no existe'], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $user->email)->delete();

        return response()->json(['message' => 'Cuenta activada. Ahora puedes iniciar sesión.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Gestión de Usuarios
    |--------------------------------------------------------------------------
    */

    public function listUsers()
    {
        $user = JWTAuth::user();

        if ($user->hasRole('super-admin')) {
            $users = User::with('roles', 'branches', 'companies')->get();
        } else {
            // Usar el nuevo método getUsersInScope()
            $users = $user->getUsersInScope();
            $users->load('roles', 'branches', 'companies');
        }

        return response()->json($users);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->only([
            'first_name',
            'last_name',
            'position',
            'phone_number',
            'address',
            'vacation_days',
            'administrative_days',
        ]);

        $user->update($data);

        if ($request->has('work_permits')) {
            $user->work_permits = json_encode($request->work_permits);
            $user->save();
        }

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json(['message' => 'User updated']);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function perfil()
    {
        $user = JWTAuth::parseToken()->authenticate()->load(
            'branch.subsidiary.company',
            'scopeRoles.role',
            'payslips',
            'personalization',
            'commune.province.region'
        );

        $avatarMedia = $user->getFirstMedia('avatar') ?: null;

        $avatar = [
            'exists'       => (bool) $avatarMedia,
            'original_url' => $avatarMedia ? $avatarMedia->getUrl() : null,
            'sm'           => $user->getFirstMediaUrl('avatar', 'avatar_sm'),
            'md'           => $user->getFirstMediaUrl('avatar', 'avatar_md'),
            'lg'           => $user->getFirstMediaUrl('avatar', 'avatar_lg'),
            // opcionalmente, metadata útil:
            'media_id'     => $avatarMedia?->id,
            'file_name'    => $avatarMedia?->file_name,
            'mime_type'    => $avatarMedia?->mime_type,
            'size'         => $avatarMedia?->size,
        ];

        return response()->json([
            'user' => [
                'pk'                 => $user->id,
                'email'              => $user->email,
                'first_name'         => $user->first_name,
                'second_name'        => $user->middle_name,
                'last_name'          => $user->last_name,
                'second_last_name'   => $user->second_last_name,
                'rut'                => $user->rut,
                'celular'            => $user->phone_number,
                'genero'             => $user->gender,
                'fecha_nacimiento'   => null,
                'is_staff'           => $user->hasAnyRole(['super-admin', 'company-admin']),
                'image'              => $avatar,
                'estado_civil'       => null,
                'nacionalidad'       => null,
                'fecha_ingreso'      => optional($user->payslip)->entry_date,
                'fecha_contrato'     => optional($user->payslip)->entry_date,
                'fono_fijo'          => null,
                'cargo'              => $user->position,
                'direccion'          => $user->address,
                // 'region'             => optional(optional(optional($user->commune)->province)->region)->name,
                // 'provincia'          => optional(optional($user->commune)->province)->name,
                'comuna_id'             => optional($user->commune)->id,
                'personalizacion'    => $user->personalization ? [
                    'id'                  => $user->personalization->id,
                    'fecha_creacion'      => $user->personalization->created_at,
                    'fecha_modificacion'  => $user->personalization->updated_at,
                    'tema'                => $user->personalization->tema,
                    'font_size'           => $user->personalization->font_size,
                    'usuario'             => $user->personalization->usuario,
                    'sucursal_principal'  => $user->personalization->sucursal_principal,
                    'empresa'             => $user->personalization->empresa,
                ] : null,
            ],
            'permisos'  => $user->getAllPermissions()->pluck('name'),
            'roles'     => $user->getRoleNames(),
            'branch'    => $user->branch ? [
                'id'            => $user->branch->id,
                'branch_name'   => $user->branch->branch_name,
                'subsidiary_id' => $user->branch->subsidiary_id,
                'subsidiary'    => $user->branch->subsidiary ? [
                    'id'            => $user->branch->subsidiary->id,
                    'subsidiary_name' => $user->branch->subsidiary->subsidiary_name,
                    'company_id'    => $user->branch->subsidiary->company_id,
                    'company'       => $user->branch->subsidiary->company ? [
                        'id'            => $user->branch->subsidiary->company->id,
                        'company_name' => $user->branch->subsidiary->company->company_name
                    ] : null,
                ] : null,
            ] : null,
        ])->setStatusCode(200, 'Perfil de usuario obtenido correctamente');
    }

    /*
    |--------------------------------------------------------------------------
    | Gestión Multi-Empresa
    |--------------------------------------------------------------------------
    */

    public function switchCompany(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $companyId = $request->company_id;

        // Verificar que el usuario pertenece a esta empresa
        if (!$user->companies->contains('id', $companyId)) {
            return response()->json(['error' => 'No autorizado para acceder a esta empresa'], 403);
        }

        // Desmarcar empresa principal actual
        $user->companies()->wherePivot('is_primary', true)->updateExistingPivot($user->companies()->wherePivot('is_primary', true)->first()->id ?? 0, ['is_primary' => false]);

        // Marcar nueva empresa como principal
        $user->companies()->updateExistingPivot($companyId, ['is_primary' => true]);

        // Obtener personalización para esta empresa
        $personalization = $user->getPersonalizationForCompany($companyId);

        return response()->json([
            'message' => 'Empresa cambiada exitosamente',
            'company' => $user->companies()->where('company_id', $companyId)->first(),
            'personalization' => $personalization,
        ]);
    }

    public function getAvailableCompanies()
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        $companies = $user->companies()->with(['subsidiaries.branches'])->get();
        
        return response()->json([
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'company_name' => $company->company_name,
                    'is_primary' => $company->pivot->is_primary,
                    'position_in_company' => $company->pivot->position_in_company,
                    'subsidiaries_count' => $company->subsidiaries->count(),
                    'branches_count' => $company->subsidiaries->sum(fn($sub) => $sub->branches->count()),
                ];
            })
        ]);
    }

}
