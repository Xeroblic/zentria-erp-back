<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\SubsidiaryBriefResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request)
    {
        // Solo se permite crear una única empresa principal
        if (Company::exists()) {
            return response()->json(['error' => 'Ya existe una empresa registrada.'], 409);
        }

        $this->authorize('create', Company::class);

        $company = Company::create($request->validated());
        $company->load(['commune']);

        return new CompanyResource($company);
    }

    public function show()
    {
        // Obtener la empresa del usuario autenticado
        $user = Auth::user();
        $company = null;
        
        // Si el usuario es super-admin, puede ver cualquier empresa
        if ($user->hasRole('super-admin')) {
            // Para super-admin, mostrar su empresa principal o la primera disponible
            $userCompanies = $user->companies;
            if ($userCompanies->isNotEmpty()) {
                $company = $userCompanies->first();
            } else {
                $company = Company::first();
            }
        } else {
            // Para otros usuarios, solo ver su empresa asociada
            $userCompanies = $user->companies;
            if ($userCompanies->isEmpty()) {
                return response()->json(['error' => 'Usuario no tiene empresas asociadas'], 403);
            }
            $company = $userCompanies->first();
        }

        if (!$company) {
            return response()->json(['error' => 'No se encontró empresa'], 404);
        }

        // Cargar relaciones relevantes
        $company->load(['subsidiaries.commune', 'commune']);

        $this->authorize('view', $company);

        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request, $id = null)
    {
        // Si no se proporciona ID o es 1, obtener la empresa principal
        if (!$id || $id == 1) {
            $company = Company::with(['subsidiaries.commune', 'commune'])->firstOrFail();
        } else {
            $company = Company::with(['subsidiaries.commune', 'commune'])->findOrFail($id);
        }

        $this->authorize('update', $company);

        $company->update($request->validated());

        // Refrescar el modelo para obtener los datos actualizados
        $company->refresh();
        $company->load(['subsidiaries.commune', 'commune']);

        return new CompanyResource($company);
    }

    /**
     * Actualizar solo la comuna de la empresa
     */
    public function updateCommune(Request $request, int $id)
    {
        $company = Company::findOrFail($id);
        $this->authorize('update', $company);

        $data = $request->validate([
            'commune_id' => 'nullable|integer|exists:communes,id',
        ]);

        $company->commune_id = $data['commune_id'] ?? null;
        $company->save();

        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $company->load($with);
        } else {
            $company->load('commune');
        }

        return new CompanyResource($company);
    }

    /**
     * Obtener todos los usuarios relacionados a una empresa (y sus subniveles)
     */
    public function getUsers($companyId)
    {
        $company = Company::findOrFail($companyId);
        $this->authorize('view', $company);

        $users = User::fromCompany($company->id)
            ->with(['branch.subsidiary.company', 'roles'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'rut' => $user->rut,
                    'role' => optional($user->roles->first())->name,
                    'branch_name' => optional($user->branch)->branch_name,
                    'subsidiary_name' => optional($user->branch?->subsidiary)->subsidiary_name,
                    'company_name' => optional($user->company)->company_name,
                ];
            });

        return response()->json([
            'usuarios' => $users
        ]);
    }



    /**
     * Obtener subempresas de una empresa
     */
    public function subsidiaries($id)
    {
        $user = Auth::user();
        
        // Verificar que el usuario pertenece a la empresa solicitada
        $userCompanyIds = $user->companies->pluck('id')->toArray();
        
        if (!in_array($id, $userCompanyIds)) {
            return response()->json([
                'error' => 'No tienes acceso a esta empresa'
            ], 403);
        }

        $company = Company::findOrFail($id);

        $this->authorize('view', $company);

        $subsidiaries = $company->subsidiaries()
            ->visibleTo($user)
            ->with(['branches.commune', 'commune'])
            ->get();

        return response()->json([
            'subempresas' => $subsidiaries,
        ]);
    }

    /**
     * Obtener información de la empresa del usuario actual
     */
    public function myCompany()
    {
        $user = Auth::user();
        
        // Obtener la primera empresa del usuario
        $company = $user->companies->first();
        
        if (!$company) {
            return response()->json([
                'error' => 'No tienes una empresa asignada'
            ], 404);
        }

        $this->authorize('view', $company);

        $company->loadMissing(['subsidiaries.commune', 'commune']);
        return response()->json([
            'data' => new CompanyResource($company)
        ]);
    }

    /**
     * Obtener subempresas de la empresa del usuario actual
     */
    public function myCompanySubsidiaries()
    {
        $user = Auth::user();
        
        // Obtener la primera empresa del usuario (o podrías usar lógica más compleja)
        $company = $user->companies->first();
        
        if (!$company) {
            return response()->json([
                'error' => 'No tienes una empresa asignada'
            ], 404);
        }

        $this->authorize('view', $company);

        // Cargar solo lo necesario, y filtrar por acceso contextual del usuario
        $subsidiaries = $company->subsidiaries()
            ->visibleTo($user)
            ->with(['branches.commune'])
            ->get();

        return response()->json([
            'subempresas' => SubsidiaryBriefResource::collection($subsidiaries),
        ]);
    }

    /**
     * Obtener usuarios de la empresa del usuario actual
     */
    public function myCompanyUsers()
    {
        $user = Auth::user();
        
        // Obtener la primera empresa del usuario
        $company = $user->companies->first();
        
        if (!$company) {
            return response()->json([
                'error' => 'No tienes una empresa asignada'
            ], 404);
        }

        $this->authorize('view', $company);

        // Obtener usuarios de la empresa
        $users = $company->users()->with(['roles', 'permissions'])->get();

        return response()->json([
            'usuarios' => $users,
            'empresa' => [
                'id' => $company->id,
                'nombre' => $company->company_name
            ]
        ]);
    }

    /**
     * Actualizar la empresa del usuario actual
     */
    public function updateMyCompany(Request $request)
    {
        $user = Auth::user();
        
        // Obtener la primera empresa del usuario
        $company = $user->companies->first();
        
        if (!$company) {
            return response()->json([
                'error' => 'No tienes una empresa asignada'
            ], 404);
        }

        $this->authorize('update', $company);

        // Validaciones (usar las mismas del método update original)
        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_rut' => 'sometimes|string|max:20',
            'company_phone' => 'sometimes|string|max:20',
            'company_email' => 'sometimes|email|max:255',
            'company_address' => 'sometimes|string|max:500',
            'commune_id' => 'sometimes|integer|exists:communes,id',
            'company_website' => 'sometimes|url|max:255',
            'company_logo' => 'sometimes|string', // Para base64
            'company_description' => 'sometimes|string|max:1000',
        ]);

        $company->update($validated);

        return new CompanyResource($company);
    }

}
