<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Http\Resources\BranchResource;

class BranchController extends Controller
{

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('view', $branch);

        return new BranchResource($branch);
    }

    /* Se agrega para dar solución a error al hacer un post a branch, en caso de no necesitarse se elimina método + endpoint */
    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);

        $branch = Branch::create($request->all());
        return response()->json($branch, 201);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('update', $branch);

        $branch->update($request->all());

        return response()->json($branch);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('delete', $branch);

        $branch->delete();

        return response()->json(['message' => 'Sucursal eliminada.']);
    }
    
}
