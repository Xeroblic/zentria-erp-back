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

        return $query->select('id', 'first_name', 'last_name', 'email')->get();
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
}
