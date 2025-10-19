<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $user = $request->user();

        $query = User::query();

        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $query->with($with);
        }

        // Usar el nuevo sistema multi-empresa
        if ($user->companies()->exists()) {
            // Si el usuario tiene empresas, mostrar usuarios de sus empresas
            $companyIds = $user->companies()->pluck('company_id');
            $query->whereHas('companies', function($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        } else {
            // Si no tiene empresa, mostrar usuarios de la misma sucursal principal
            $query->where('primary_branch_id', $user->primary_branch_id);
        }

        return $query->select('id', 'first_name', 'last_name', 'email', 'image')->get();
    }

    public function show(Request $request, $id)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = User::query();
        if (!empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id, ['id', 'first_name', 'last_name', 'email', 'image']);
    }

    public function me(Request $request) 
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->ful_name,
            'email' => $user->email,
            'roles'        => $user->getRoleNames(),
            'permissions'  => $user->getPermissionNames(),
            'is_active' => $user->is_active,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
        ]);
    }

    public function updateRoles(Request $request, $id)
    {
        $this->authorize('update', User::class);

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::findOrFail($id);

        $user->syncRoles($request->roles);

        return response()->json([
            'message' => 'Roles actualizados correctamente',
            'roles' => $user->getRoleNames(),

        ]) ->setStatusCode(200);
    }

    public function updateCommune(Request $request, $id)
    {
        // Permitir que un usuario actualice su propia comuna o alguien con permiso user.edit
        $authUser = $request->user();
        if ((int)$authUser->id !== (int)$id && !$authUser->can('user.edit')) {
            abort(403, 'No autorizado para actualizar la comuna de este usuario');
        }

        $data = $request->validate([
            'commune_id' => 'required|integer|exists:communes,id',
        ]);

        $user = User::findOrFail($id);
        $user->commune_id = $data['commune_id'];
        $user->save();

        // Retornar datos básicos incluyendo comuna_id; permitir incluir relación con ?with=commune
        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $user->load($with);
        }

        return response()->json([
            'message' => 'Comuna actualizada correctamente',
            'user' => $user->only(['id','first_name','last_name','email','commune_id']) + (
                $user->relationLoaded('commune') ? ['commune' => $user->commune] : []
            ),
        ]);
    }

    public function updateMyCommune(Request $request)
    {
        $authUser = $request->user();

        $data = $request->validate([
            'commune_id' => 'required|integer|exists:communes,id',
        ]);

        $authUser->commune_id = $data['commune_id'];
        $authUser->save();

        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $authUser->load($with);
        }

        return response()->json([
            'message' => 'Comuna actualizada correctamente',
            'user' => $authUser->only(['id','first_name','last_name','email','commune_id']) + (
                $authUser->relationLoaded('commune') ? ['commune' => $authUser->commune] : []
            ),
        ]);
    }
}
