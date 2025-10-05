<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;

class BranchProductsController extends Controller
{
    public function index(Request $request, Branch $branch)
    {
        $this->authorize('viewAny', [Product::class, $branch]);

        $q = Product::query()->fromBranch($branch->id)->with(['brand','categories']);

        // Texto
        if ($s = $request->get('q')) $q->search($s);
        // Filtros básicos
        if ($brandId = $request->integer('brand_id')) $q->where('brand_id', $brandId);
        if (!is_null($request->get('is_active'))) $q->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOL));
        if ($type = $request->get('product_type')) $q->where('product_type', $type);
        if (!is_null($request->get('serial_tracking'))) $q->where('serial_tracking', filter_var($request->get('serial_tracking'), FILTER_VALIDATE_BOOL));
        if ($min = $request->get('min_price')) $q->where('price', '>=', (float)$min);
        if ($max = $request->get('max_price')) $q->where('price', '<=', (float)$max);
        if ($cat = $request->integer('category_id')) $q->whereHas('categories', fn($w)=>$w->where('categories.id',$cat));
        // Filtros por atributos JSONB: attr[color]=black → WHERE attributes_json->>'color' = 'black'
        // filtros por atributos exactos (ya tenías)
        if (is_array($request->get('attr'))) {
            foreach ($request->get('attr') as $k => $v) {
                $q->whereRaw('(attributes_json::jsonb)->> ? = ?', [$k, (string)$v]);
            }
        }

        // parciales por clave
        if (is_array($request->get('attr_like'))) {
            foreach ($request->get('attr_like') as $path => $val) {
                $q->whereRaw(
                    '(attributes_json::jsonb #>> string_to_array(?, \'.\')) ILIKE ?',
                    [$path, '%'.$val.'%']
                );
            }
        }

        // parcial en cualquier valor del JSON (elige una de las dos variantes):
        if ($term = $request->get('attr_any_like')) {
            // Variante A (simple, con trigram index opcional)
            $q->whereRaw('(attributes_json::text) ILIKE ?', ['%'.$term.'%']);
        }

        $q->orderBy($request->get('order_by','name'), $request->get('order_dir','asc'));

        return ProductResource::collection(
            $q->paginate($request->integer('per_page', 15))->appends($request->query())
        );
    }

    public function store(StoreProductRequest $request, Branch $branch)
    {
        $data = $request->validated();
        $data['branch_id'] = $branch->id;
        $product = Product::create($data);

        // ⬇️ asigna categorías si vinieron
        if (!empty($data['category_ids'])) {
            $ids = collect($data['category_ids'])
                ->mapWithKeys(fn($id) => [$id => ['assigned_at' => now()]])
                ->all();

            $product->categories()->sync($ids);
        }

        return ProductResource::make($product->load(['brand','categories']));
    }

    public function show(Branch $branch, Product $product)
    {
        $this->authorize('view', $product);
        if ($product->branch_id !== $branch->id) abort(404);
        return ProductResource::make($product->load(['brand','categories']));
    }

    public function update(UpdateProductRequest $request, Branch $branch, Product $product)
    {
        if ($product->branch_id !== $branch->id) abort(404);

        $product->update($request->validated());

        // ⬇️ si enviaron category_ids, sincroniza
        if ($request->has('category_ids')) {
            $ids = collect($request->input('category_ids', []))
                ->mapWithKeys(fn($id) => [$id => ['assigned_at' => now()]])
                ->all();

            $product->categories()->sync($ids);
        }

        return ProductResource::make($product->load(['brand','categories']));
    }

    public function destroy(Branch $branch, Product $product)
    {
        $this->authorize('delete', $product);
        if ($product->branch_id !== $branch->id) abort(404);
        $product->delete();
        return response()->json(['deleted'=>true]);
    }
}
