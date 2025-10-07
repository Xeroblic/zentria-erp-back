<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\TogglesActiveFlag;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Branch;
use App\Models\Brand;
use Illuminate\Http\Request;

class BranchBrandsController extends Controller
{

    use TogglesActiveFlag;

    public function index(Request $request, Branch $branch)
    {
        $this->authorize('viewAny', [Brand::class, $branch]);

        $branchId = auth()->user()->primary_branch_id;
        
        $q = Brand::query()
        ->select('brands.*') 
        ->where('brands.branch_id', $branch->id)
        ->addSelect([
            'products_count' => function ($query) use ($branchId) {
                $query->selectRaw('COUNT(*)')
                      ->from('products')
                      ->whereColumn('products.brand_id', 'brands.id')
                      ->where('products.branch_id', $branchId);
            }
        ]);

        //TODO: VENTAS ACUMULADAS DE LA MARCA
        // Ventas: solo si existe el módulo/tabla
        // if (config('features.sales', false)) {
        //     $q->addSelect([
        //         'sales_count' => SaleItem::selectRaw('COUNT(DISTINCT sale_items.sale_id)')
        //             ->join('products', 'products.id', '=', 'sale_items.product_id')
        //             ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
        //             ->whereColumn('products.brand_id', 'brands.id')
        //             ->where('sales.status', SaleStatus::CONFIRMED->value) // ajusta si usas string/enum
        //             // si necesitas acotar por sucursal/empresa:
        //             ->where('sales.company_id', $companyId),
        //     ]);
        // }

        if ($s = trim((string)$request->get('q'))) {
            $q->whereRaw('name ILIKE ?', ['%'.$s.'%']);
        }
        $q->orderBy('id', 'asc');
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

    public function toggleStatus(Branch $branch, Brand $brand)
    {
        return $this->toggleModelActive($brand, 'Marca');
    }
}
