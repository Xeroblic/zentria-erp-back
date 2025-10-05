<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Product\SyncProductCategoriesRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;

class ProductCategoryController extends Controller
{
    public function index(Branch $branch, Product $product) {
        $this->authorize('view', $product);
        if ($product->branch_id !== $branch->id) abort(404);

        // sin soft-delete, listar normal
        return CategoryResource::collection(
            $product->categories()->orderBy('name')->get()
        );
    }

    public function sync(Request $r, Branch $branch, Product $product) {
        $this->authorize('update', $product);
        if ($product->branch_id !== $branch->id) abort(404);

        $data = $request->validate([
            'category_ids'   => ['required','array'],
            'category_ids.*' => ['integer','exists:categories,id'],
        ]);

        $ids = collect($data['category_ids'])
            ->mapWithKeys(fn($id)=>[$id=>['assigned_at'=>now()]])
            ->all();

        // elimina/crea en pivot (hard delete), nada de deleted_at
        $product->categories()->sync($ids);

        return CategoryResource::collection(
            $product->categories()->orderBy('name')->get()
        );
    }

    public function attach(Branch $branch, Product $product, Category $category) {
        $this->authorize('update', $product);
        if ($product->branch_id !== $branch->id) abort(404);

        $product->categories()->syncWithoutDetaching([
            $category->id => ['assigned_at'=>now()]
        ]);

        return response()->json(['attached'=>true]);
    }


    public function detach(Branch $branch, Product $product, Category $category) {
        $this->authorize('update', $product);
        if ($product->branch_id !== $branch->id) abort(404);

        // hard delete del registro pivot
        $product->categories()->detach($category->id);

        return response()->json(['detached'=>true]);
    }

    /* En caso */

    // public function restore(Branch $branch, Product $product, Category $category) {
    //     $this->authorize('update', $product);
    //     if ($product->branch_id !== $branch->id) abort(404);

    //     $product->categories()->updateExistingPivot($category->id, [
    //         'deleted_at'=>null, 'assigned_at'=>now(), 'updated_at'=>now(),
    //     ]);
    //     return response()->json(['restored'=>true]);
    // }
}