<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Http\Resources\BranchResource;

class BranchController extends Controller
{

    public function show($id)
    {
        $branch = Branch::with('commune')->findOrFail($id);
        $this->authorize('view', $branch);

        return new BranchResource($branch);
    }

    /* Se agrega para dar solución a error al hacer un post a branch, en caso de no necesitarse se elimina método + endpoint */
    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);

        $validated = $request->validate([
            'subsidiary_id' => 'required|exists:subsidiaries,id',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string|max:500',
            'commune_id' => 'nullable|integer|exists:communes,id',
            'branch_phone' => 'nullable|string|max:50',
            'branch_email' => 'nullable|email|max:255',
            'branch_status' => 'nullable|string|max:50',
            'branch_manager_name' => 'nullable|string|max:255',
            'branch_manager_phone' => 'nullable|string|max:50',
            'branch_manager_email' => 'nullable|email|max:255',
            'branch_opening_hours' => 'nullable|string|max:255',
            'branch_location' => 'nullable|string|max:255',
        ]);

        $branch = Branch::create($validated);
        $branch->load('commune');
        return (new BranchResource($branch))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('update', $branch);

        $validated = $request->validate([
            'subsidiary_id' => 'sometimes|exists:subsidiaries,id',
            'branch_name' => 'sometimes|string|max:255',
            'branch_address' => 'sometimes|nullable|string|max:500',
            'commune_id' => 'sometimes|nullable|integer|exists:communes,id',
            'branch_phone' => 'sometimes|nullable|string|max:50',
            'branch_email' => 'sometimes|nullable|email|max:255',
            'branch_status' => 'sometimes|nullable|string|max:50',
            'branch_manager_name' => 'sometimes|nullable|string|max:255',
            'branch_manager_phone' => 'sometimes|nullable|string|max:50',
            'branch_manager_email' => 'sometimes|nullable|email|max:255',
            'branch_opening_hours' => 'sometimes|nullable|string|max:255',
            'branch_location' => 'sometimes|nullable|string|max:255',
        ]);

        $branch->update($validated);
        $branch->load('commune');
        return new BranchResource($branch);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('delete', $branch);

        $branch->delete();

        return response()->json(['message' => 'Sucursal eliminada.']);
    }
    
    /**
     * Actualizar solo la comuna de la sucursal
     */
    public function updateCommune(Request $request, int $id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('update', $branch);

        $data = $request->validate([
            'commune_id' => 'nullable|integer|exists:communes,id',
        ]);

        $branch->commune_id = $data['commune_id'] ?? null;
        $branch->save();

        $with = array_filter(explode(',', (string) $request->query('with')));
        if (!empty($with)) {
            $branch->load($with);
        } else {
            $branch->load('commune');
        }

        return new BranchResource($branch);
    }
}
