<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;

class BranchController extends Controller
{
    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        $this->authorize('view', $branch);

        return response()->json($branch);
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
