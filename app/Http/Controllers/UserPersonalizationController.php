<?php

namespace App\Http\Controllers;

use App\Models\UserPersonalization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserPersonalizationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'tema' => 'nullable|integer|min:1',
            'font_size' => 'nullable|integer|min:10|max:18',
            'tcolor' => 'nullable|string',
            'tcolor_int' => 'nullable|string',
            'sucursal_principal' => 'nullable|exists:branches,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        // Usar JWTAuth para obtener el usuario autenticado
        $user = JWTAuth::parseToken()->authenticate();
        
        // Verificar que el usuario pertenece a la empresa si se especifica company_id
        if ($request->has('company_id') && $request->company_id) {
            $hasAccess = $user->companies()->where('companies.id', $request->company_id)->exists();
            if (!$hasAccess) {
                return response()->json(['error' => 'No tienes acceso a esta empresa'], 403);
            }
        }
        
        $userPersonalization = $user
            ->personalization()
            ->firstOrCreate([]);

        // Actualizar los campos permitidos
        $fieldsToUpdate = $request->only(['tema', 'font_size', 'sucursal_principal', 'company_id', 'tcolor', 'tcolor_int']);
        $userPersonalization->fill($fieldsToUpdate)->save();

        // Si se cambió la empresa, obtener información actualizada
        if ($request->has('company_id')) {
            $companies = $user->companies()->with(['subsidiaries.branches'])->get();
            $currentCompany = $companies->firstWhere('id', $userPersonalization->company_id);
            
            $subsidiaries = $currentCompany ? $currentCompany->subsidiaries->map(function ($subsidiary) {
                return [
                    'id' => $subsidiary->id,
                    'subsidiary_name' => $subsidiary->subsidiary_name,
                    'branches_count' => $subsidiary->branches->count(),
                    'branches' => $subsidiary->branches->map(function ($branch) {
                        return [
                            'id' => $branch->id,
                            'branch_name' => $branch->branch_name,
                        ];
                    })
                ];
            }) : collect();

            return response()->json([
                'personalization' => $userPersonalization,
                'current_company' => $currentCompany ? [
                    'id' => $currentCompany->id,
                    'company_name' => $currentCompany->company_name,
                    'subsidiaries' => $subsidiaries
                ] : null,
                'message' => 'Personalización actualizada exitosamente'
            ]);
        }

        return response()->json([
            'personalization' => $userPersonalization,
            'message' => 'Personalización actualizada exitosamente'
        ]);
    }

    /**
     * Cambiar empresa activa del usuario
     */
    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        
        // Verificar que el usuario tiene acceso a esta empresa
        $hasAccess = $user->companies()->where('companies.id', $request->company_id)->exists();
        if (!$hasAccess) {
            return response()->json(['error' => 'No tienes acceso a esta empresa'], 403);
        }

        // Actualizar personalización con nueva empresa
        $userPersonalization = $user->personalization()->firstOrCreate([]);
        $userPersonalization->company_id = $request->company_id;
        $userPersonalization->save();

        // Obtener información de la nueva empresa
        $companies = $user->companies()->with(['subsidiaries.branches'])->get();
        $currentCompany = $companies->firstWhere('id', $request->company_id);
        
        $subsidiaries = $currentCompany->subsidiaries->map(function ($subsidiary) {
            return [
                'id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->subsidiary_name,
                'branches_count' => $subsidiary->branches->count(),
                'branches' => $subsidiary->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'branch_name' => $branch->branch_name,
                    ];
                })
            ];
        });
 
        
        return response()->json([
            'message' => 'Empresa cambiada exitosamente',
            'personalization' => $userPersonalization,
            'current_company' => [
                'id' => $currentCompany->id,
                'company_name' => $currentCompany->company_name,
                'subsidiaries' => $subsidiaries
            ]
        ]);
    }


    public function show()
    {
        // Usar JWTAuth para obtener el usuario autenticado
        $user = JWTAuth::parseToken()->authenticate();
        $userPersonalization = $user->personalization;

        if (!$userPersonalization) {
            // Primera vez: crear personalización por defecto con empresa primaria
            $primaryCompany = $user->companies()->wherePivot('is_primary', true)->first();
            
            $userPersonalization = $user->personalization()->create([
                'tema' => 1,
                'font_size' => 14,
                'tcolor' => 'emerald',
                'tcolor_int' => '500',
                'company_id' => $primaryCompany ? $primaryCompany->id : null,
                'sucursal_principal' => null
            ]);
        }

        // Obtener información de empresas y subsidiarias del usuario
        $companies = $user->companies()->with(['subsidiaries.branches'])->get();
        
        // Obtener la empresa actual de la personalización
        $currentCompany = $companies->firstWhere('id', $userPersonalization->company_id) 
                         ?? $companies->firstWhere('pivot.is_primary', true) 
                         ?? $companies->first();

        // Preparar lista de subsidiarias para la empresa actual
        $subsidiaries = $currentCompany ? $currentCompany->subsidiaries->map(function ($subsidiary) {
            return [
                'id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->subsidiary_name,
                'branches_count' => $subsidiary->branches->count(),
                'branches' => $subsidiary->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'branch_name' => $branch->branch_name,
                    ];
                })
            ];
        }) : collect();

        return response()->json([
            'personalization' => [
                'id' => $userPersonalization->id,
                'user_id' => $userPersonalization->user_id,
                'tema' => $userPersonalization->tema,
                'font_size' => $userPersonalization->font_size,
                'tcolor' => $userPersonalization->tcolor,
                'tcolor_int' => $userPersonalization->tcolor_int,
                'sucursal_principal' => $userPersonalization->sucursal_principal,
                'company_id' => $userPersonalization->company_id,
                'created_at' => $userPersonalization->created_at,
                'updated_at' => $userPersonalization->updated_at,
            ],
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'company_name' => $company->company_name,
                    'is_primary' => $company->pivot->is_primary,
                    'position_in_company' => $company->pivot->position_in_company,
                    'subsidiaries_count' => $company->subsidiaries->count(),
                    'branches_count' => $company->subsidiaries->sum(fn($sub) => $sub->branches->count()),
                ];
            }),
            'current_company' => $currentCompany ? [
                'id' => $currentCompany->id,
                'company_name' => $currentCompany->company_name,
                'subsidiaries' => $subsidiaries
            ] : null
        ]);
    }


}
