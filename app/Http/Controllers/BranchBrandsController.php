<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Branch;
use App\Models\Brand;
use Illuminate\Http\Request;

class BranchBrandsController extends Controller
{
    public function index(Request $request, Branch $branch)
    {
        $this->authorize('viewAny', [Brand::class, $branch]);
        
        $q = $branch->brands(); // <— ahora sí existe

        if ($s = trim((string)$request->get('q'))) {
            $q->whereRaw('name ILIKE ?', ['%'.$s.'%']);
        }
        $q->orderBy('name');
        return BrandResource::collection(
            $q->paginate($request->integer('per_page', 15))->appends($request->query())
        );
    }

    public function store(StoreBrandRequest $request, Branch $branch)
    {
        // branch_id se setea automáticamente por la relación
        $brand = $branch->brands()->create([
            'name' => (string) $request->string('name'),
        ]);

        return BrandResource::make($brand);
    }

    public function show(Branch $branch, Brand $brand)
    {
        $this->authorize('view', $brand);
        if ($brand->branch_id !== $branch->id) abort(404);
        return BrandResource::make($brand);
    }

    public function update(UpdateBrandRequest $request, Branch $branch, Brand $brand)
    {
        if ($brand->branch_id !== $branch->id) abort(404);
        $brand->update($request->validated());
        return BrandResource::make($brand);
    }

    public function destroy(Branch $branch, Brand $brand)
    {
        $this->authorize('delete', $brand);
        if ($brand->branch_id !== $branch->id) abort(404);
        
        $brand->delete();
        return response()->json(['deleted'=>true]);
    }
}
