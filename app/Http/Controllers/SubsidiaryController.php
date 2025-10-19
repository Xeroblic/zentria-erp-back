<?php

namespace App\Http\Controllers;

use App\Models\Subsidiary;
use App\Http\Resources\SubsidiaryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubsidiaryController extends Controller
{
    /**
     * Mostrar listado de subsidiarias.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Subsidiary::class);
        $query = Subsidiary::visibleTo(Auth::user());

        // Filtro opcional por empresa
        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->query('company_id'));
        }

        // with=commune,branches,branches.commune
        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $query->with($with);
        }

        return SubsidiaryResource::collection($query->get());
    }

    /**
     * Almacenar una nueva subsidiaria.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Subsidiary::class);

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'subsidiary_name' => 'required|string|max:255',
            'subsidiary_rut' => 'nullable|string|max:30',
            'subsidiary_website' => 'nullable|url|max:255',
            'subsidiary_phone' => 'nullable|string|max:50',
            'subsidiary_address' => 'nullable|string|max:500',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'subsidiary_email' => 'nullable|email|max:255',
            'subsidiary_manager_name' => 'nullable|string|max:255',
            'subsidiary_manager_phone' => 'nullable|string|max:50',
            'subsidiary_manager_email' => 'nullable|email|max:255',
            'subsidiary_status' => 'nullable|string|max:50',
        ]);

        $subsidiary = Subsidiary::create($validated);
        $subsidiary->load(['commune']);

        return (new SubsidiaryResource($subsidiary))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una subsidiaria específica.
     */
    public function show(string $id)
    {
        $subsidiary = Subsidiary::with(['commune', 'branches.commune'])->findOrFail($id);
        $this->authorize('view', $subsidiary);

        return new SubsidiaryResource($subsidiary);
    }

    /**
     * Actualizar una subsidiaria específica.
     */
    public function update(Request $request, string $id)
    {
        $subsidiary = Subsidiary::findOrFail($id);
        $this->authorize('update', $subsidiary);

        $validated = $request->validate([
            'company_id' => 'sometimes|integer|exists:companies,id',
            'subsidiary_name' => 'sometimes|string|max:255',
            'subsidiary_rut' => 'sometimes|nullable|string|max:30',
            'subsidiary_website' => 'sometimes|nullable|url|max:255',
            'subsidiary_phone' => 'sometimes|nullable|string|max:50',
            'subsidiary_address' => 'sometimes|nullable|string|max:500',
            'commune_id' => 'sometimes|nullable|integer|exists:communes,id',
            'subsidiary_email' => 'sometimes|nullable|email|max:255',
            'subsidiary_manager_name' => 'sometimes|nullable|string|max:255',
            'subsidiary_manager_phone' => 'sometimes|nullable|string|max:50',
            'subsidiary_manager_email' => 'sometimes|nullable|email|max:255',
            'subsidiary_status' => 'sometimes|nullable|string|max:50',
        ]);

        $subsidiary->update($validated);
        $subsidiary->load(['commune', 'branches.commune']);

        return new SubsidiaryResource($subsidiary);
    }

    /**
     * Eliminar una subsidiaria específica.
     */
    public function destroy(string $id)
    {
        $subsidiary = Subsidiary::findOrFail($id);
        $this->authorize('delete', $subsidiary);

        $subsidiary->delete();

        return response()->json(['message' => 'Subempresa eliminada.']);
    }

    /**
     * Actualizar solo la comuna de la subsidiaria
     */
    public function updateCommune(Request $request, int $id)
    {
        $subsidiary = Subsidiary::findOrFail($id);
        $this->authorize('update', $subsidiary);

        $data = $request->validate([
            'commune_id' => 'nullable|integer|exists:communes,id',
        ]);

        $subsidiary->commune_id = $data['commune_id'] ?? null;
        $subsidiary->save();

        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $subsidiary->load($with);
        } else {
            $subsidiary->load('commune');
        }

        return new SubsidiaryResource($subsidiary);
    }
}
