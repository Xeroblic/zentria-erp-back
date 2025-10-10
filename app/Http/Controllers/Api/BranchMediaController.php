<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadLibraryMediaRequest;
use App\Http\Requests\Media\AttachFromLibraryRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media\Media as SpatieMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchMediaController extends Controller
{
    // POST /api/branches/{branch}/library/media
    public function uploadToLibrary(UploadLibraryMediaRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch); // aplica tu BranchPolicy/permissions

        $media = $branch->addMediaFromRequest('file')
            ->withCustomProperties([
                'alt'  => $request->input('alt_text'),
                'tags' => $request->input('tags', []),
            ])->toMediaCollection('library');

        // Scope por Branch
        $media->branch_id = $branch->id;
        $media->save();

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumb_url' => $media->getUrl('thumb'),
            'alt' => $media->getCustomProperty('alt'),
            'tags' => $media->getCustomProperty('tags', []),
            'file_name' => $media->file_name,
            'size' => $media->size,
            'mime_type' => $media->mime_type,
            'created_at' => $media->created_at,
        ], 201);
    }

    // GET /api/branches/{branch}/library/media?q=&tag=&page=1
    public function listLibrary(Request $request, Branch $branch)
    {
        $this->authorize('view', $branch);

        $q   = $request->string('q')->toString();
        $tag = $request->string('tag')->toString();

        $query = $branch->media()
            ->where('collection_name','library')
            ->where('branch_id', $branch->id);

        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('file_name','like',"%{$q}%")
                  ->orWhere('name','like',"%{$q}%")
                  ->orWhere('custom_properties->alt','like',"%{$q}%");
            });
        }
        if ($tag !== '') {
            $query->whereJsonContains('custom_properties->tags', $tag);
        }

        $media = $query->latest()->paginate(24);

        return response()->json([
            'data' => $media->map(fn($m) => [
                'id' => $m->id,
                'thumb_url' => $m->getUrl('thumb'),
                'url' => $m->getUrl(),
                'alt' => $m->getCustomProperty('alt'),
                'tags' => $m->getCustomProperty('tags', []),
                'file_name' => $m->file_name,
                'size' => $m->size,
                'mime_type' => $m->mime_type,
            ]),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page'    => $media->lastPage(),
                'total'        => $media->total(),
            ]
        ]);
    }

    // POST /api/branches/{branch}/{type}/{id}/media/attach-from-library
    public function attachFromLibrary(AttachFromLibraryRequest $request, Branch $branch, string $type, int $id)
    {
        $model = match ($type) {
            'products'   => Product::findOrFail($id),
            'brands'     => Brand::findOrFail($id),
            'categories' => Category::findOrFail($id),
            default      => abort(404, 'Tipo no soportado'),
        };
        $this->authorize('update', $model); // y/o valida pertenencia a la Branch

        // Asegura que el asset viene de la LIBRERÍA de ESTA branch
        $asset = SpatieMedia::where('branch_id', $branch->id)
            ->where('collection_name','library')
            ->findOrFail($request->integer('library_media_id'));

        // Copia el archivo (local o S3)
        if (in_array($asset->disk, ['public','local'])) {
            $new = $model->addMedia($asset->getPath())
                ->usingFileName($asset->file_name)
                ->withCustomProperties([
                    'alt'  => $request->input('alt_text', $asset->getCustomProperty('alt')),
                    'sort' => (int) $request->input('sort_order', 0),
                    'src_library_id' => $asset->id,
                ])->toMediaCollection($request->input('collection','gallery'));
        } else {
            $stream = Storage::disk($asset->disk)->readStream($asset->getPath());
            $new = $model->addMediaFromStream($stream)
                ->usingFileName($asset->file_name)
                ->withCustomProperties([
                    'alt'  => $request->input('alt_text', $asset->getCustomProperty('alt')),
                    'sort' => (int) $request->input('sort_order', 0),
                    'src_library_id' => $asset->id,
                ])->toMediaCollection($request->input('collection','gallery'));
        }

        // Scope por Branch en el nuevo media
        $new->branch_id = $branch->id;
        $new->save();

        return response()->json([
            'status' => 'attached',
            'id' => $new->id,
            'url' => $new->getUrl(),
            'thumb_url' => $new->getUrl('thumb'),
        ], 201);
    }

    // GET /api/branches/{branch}/{type}/{id}/media?collection=gallery
    public function listFor(Branch $branch, string $type, int $id, Request $request)
    {
        $model = match ($type) {
            'products'   => Product::findOrFail($id),
            'brands'     => Brand::findOrFail($id),
            'categories' => Category::findOrFail($id),
            default      => abort(404, 'Tipo no soportado'),
        };
        $this->authorize('view', $model);

        $collection = $request->query('collection');

        // Solo media de ESTA branch
        $items = $collection ? $model->getMedia($collection) : $model->media;
        $items = $items->filter(fn($m) => $m->branch_id === $branch->id)
                       ->sortBy(fn($m) => $m->getCustomProperty('sort', 0))
                       ->values();

        return $items->map(fn($m) => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'thumb_url' => $m->getUrl('thumb'),
            'alt' => $m->getCustomProperty('alt'),
            'sort' => $m->getCustomProperty('sort', 0),
        ]);
    }

    // DELETE /api/branches/{branch}/media/{id}
    public function destroy(Branch $branch, int $id)
    {
        $media = SpatieMedia::where('branch_id', $branch->id)->findOrFail($id);
        $this->authorize('delete', $media);
        $media->delete();
        return response()->json(['status' => 'deleted']);
    }
}
