<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);
        $q = Category::query();
        if ($s = trim((string)$request->get('q'))) {
            $q->whereRaw('name ILIKE ?', ['%'.$s.'%']);
        }
        if (!is_null($request->get('parent_id'))) {
            $q->where('parent_id', $request->integer('parent_id'));
        }
        $q->withCount('children')->orderBy('name');
        return CategoryResource::collection(
            $q->paginate($request->integer('per_page', 20))->appends($request->query())
        );
    }

    public function tree(Request $request)
    {
        $this->authorize('viewAny', Category::class);
        $roots = Category::with(['children' => function($q){ $q->with('children'); }])
            ->whereNull('parent_id')->orderBy('name')->get();
        return CategoryResource::collection($roots);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
        return CategoryResource::make($category);
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);
        return CategoryResource::make($category->loadCount('children'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return CategoryResource::make($category->loadCount('children'));
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        $category->delete();
        return response()->json(['deleted'=>true]);
    }
}
