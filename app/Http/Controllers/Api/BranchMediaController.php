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
                'branch_id' => $branch->id,
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

        $q     = $request->string('q')->toString();
        $tag   = $request->string('tag')->toString();
        $scope = $request->query('scope', 'library'); // 'library' | 'all'

        // ⬅️ antes usábamos $branch->media() y collection 'library'.
        // Ahora consultamos por branch_id y opcionalmente filtramos collection.
        $query = \App\Models\Media\Media::query()
            ->where('branch_id', $branch->id);

        if ($scope === 'library') {
            $query->where('collection_name', 'library'); // solo biblioteca “pura”
        } // si scope = 'all', trae todo: product/brand/category/library

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
                'owner' => class_basename($m->model_type), // Product/Brand/Category/Branch
                'collection' => $m->collection_name,       // gallery/main/logo/banner/library
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
    public function attachFromLibrary(\App\Http\Requests\Media\AttachFromLibraryRequest $request, \App\Models\Branch $branch, string $type, int $id)
    {
        $data = $request->validated();

        return $this->attachFromLibraryCore(
            branch: $branch,
            type: $type,
            id: $id,
            libraryMediaId: (int)$data['library_media_id'],
            collection: $data['collection'] ?? 'gallery',
            sort: (int)($data['sort_order'] ?? 0),
            alt: $data['alt_text'] ?? null,
        );
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

    public function setPrimary(\Illuminate\Http\Request $request, \App\Models\Branch $branch, string $type, int $id) {
        $data = $request->validate([
            'library_media_id' => ['required','integer','exists:media,id'],
            'alt_text'         => ['nullable','string','max:255'],
        ]);

        // Colección principal según el modelo (gracias a tu trait HasModelImages)
        $modelClass = match ($type) {
            'products'   => \App\Models\Product::class,
            'brands'     => \App\Models\Brand::class,
            'categories' => \App\Models\Category::class,
            default      => abort(404, 'Tipo no soportado'),
        };
        /** @var \Spatie\MediaLibrary\HasMedia $dummy */
        $primaryCollection = $modelClass::primaryCollection();

        return $this->attachFromLibraryCore(
            branch: $branch,
            type: $type,
            id: $id,
            libraryMediaId: (int)$data['library_media_id'],
            collection: $primaryCollection,
            sort: 0,
            alt: $data['alt_text'] ?? null,
        );
    }

    private function attachFromLibraryCore(\App\Models\Branch $branch, string $type, int $id, int $libraryMediaId, string $collection = 'gallery', int $sort = 0, ?string $alt = null) 
    {
        $model = match ($type) {
            'products'   => \App\Models\Product::findOrFail($id),
            'brands'     => \App\Models\Brand::findOrFail($id),
            'categories' => \App\Models\Category::findOrFail($id),
            default      => abort(404, 'Tipo no soportado'),
        };
        $this->authorize('update', $model);

        // Asegura que el asset es de la LIBRARY de ESTA branch
        $asset = \App\Models\Media\Media::where('branch_id', $branch->id)
            ->where('collection_name','library')
            ->findOrFail($libraryMediaId);

        // Asegura que el asset existe en SU disco y lee por stream usando ruta relativa
        $relative = method_exists($asset, 'getPathRelativeToRoot')
            ? $asset->getPathRelativeToRoot()
            : ltrim(str_replace(Storage::disk($asset->disk)->path(''), '', $asset->getPath()), '/');

        $stream = Storage::disk($asset->disk)->readStream($relative);
        abort_if($stream === false, 500, 'No se pudo leer el archivo origen desde el disco.');

        // Crea el nuevo media desde el stream (sirve para local y S3)
        $new = $model->addMediaFromStream($stream)
            ->usingFileName($asset->file_name)
            ->withCustomProperties([
                'branch_id' => $branch->id,
                'alt'  => $alt ?? $asset->getCustomProperty('alt'),
                'sort' => $sort,
                'src_library_id' => $asset->id,
            ])
            ->toMediaCollection($collection);

        // Scope por Branch
        $new->branch_id = $branch->id;
        $new->save();

        return response()->json([
            'status'    => 'attached',
            'id'        => $new->id,
            'url'       => $new->getUrl(),
            'thumb_url' => $new->getUrl('thumb'),
        ], 201);
    }

    public function uploadMultipleDirect(Request $request, Branch $branch, string $type, int $id)
    {
        // Validación de entrada
        $data = $request->validate([
            'files'    => ['required','array','min:1'],
            'files.*'  => ['file','image','mimes:jpg,jpeg,png,webp,svg','max:8192'],

            // JSON opcional con metadatos por archivo (indexado por posición)
            // Ejemplo: [{ "index":0,"collection":"gallery","sort_order":0,"alt_text":"Frontal","primary":true }]
            'meta'     => ['nullable','string'],

            // Fallback simples si no usas meta JSON:
            'collection'  => ['nullable','string','max:50'],   // default: gallery
            'start_sort'  => ['nullable','integer','min:0'],   // default: 0
            'primary'     => ['nullable','in:none,first'],     // default: none
        ]);

        $model = match ($type) {
            'products'   => Product::findOrFail($id),
            'brands'     => Brand::findOrFail($id),
            'categories' => Category::findOrFail($id),
            default      => abort(404, 'Tipo no soportado'),
        };
        $this->authorize('update', $model);

        // Colecciones principales por modelo (usa tu trait si lo tienes)
        $primaryCollection = match ($type) {
            'products'   => method_exists($model, 'primaryCollection') ? $model::primaryCollection() : 'main',
            'brands'     => method_exists($model, 'primaryCollection') ? $model::primaryCollection() : 'logo',
            'categories' => method_exists($model, 'primaryCollection') ? $model::primaryCollection() : 'banner',
        };

        $defaultCollection = $request->string('collection')->toString() ?: 'gallery';
        $startSort = (int)($request->input('start_sort', 0));
        $primaryMode = $request->input('primary', 'none'); // none|first

        // Parse meta JSON (opcional)
        $metaMap = [];
        if ($request->filled('meta')) {
            $decoded = json_decode($request->string('meta'), true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (isset($row['index']) && is_int($row['index'])) {
                        $metaMap[$row['index']] = [
                            'collection' => $row['collection'] ?? null,
                            'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : null,
                            'alt_text'   => $row['alt_text'] ?? null,
                            'primary'    => (bool)($row['primary'] ?? false),
                        ];
                    }
                }
            }
        }

        $created = [];
        /** @var \Illuminate\Http\UploadedFile[] $files */
        $files = $request->file('files');

        foreach (array_values($files) as $i => $file) {
            $meta = $metaMap[$i] ?? [];

            // Determinar colección destino para este archivo
            $isPrimary = $meta['primary'] ?? false;
            if (!$isPrimary && $primaryMode === 'first' && $i === 0) {
                $isPrimary = true;
            }
            $collection = $isPrimary ? $primaryCollection : ($meta['collection'] ?? $defaultCollection);

            // Orden
            $sort = $meta['sort_order'] ?? ($startSort + $i);

            // Alt
            $alt  = $meta['alt_text'] ?? null;

            // Subir directo
            $m = $model->addMedia($file)
                ->withCustomProperties([
                    'branch_id' => $branch->id,
                    'alt'  => $alt,
                    'sort' => (int)$sort,
                    'src_upload' => 'direct-batch',
                ])
                ->toMediaCollection($collection);

            // Scope por Branch
            $m->branch_id = $branch->id;
            $m->save();

            $created[] = [
                'id'        => $m->id,
                'collection'=> $collection,
                'url'       => $m->getUrl(),
                'thumb_url' => $m->getUrl('thumb'),
                'alt'       => $alt,
                'sort'      => (int)$sort,
                'file_name' => $m->file_name,
            ];
        }

        return response()->json([
            'status'  => 'uploaded',
            'count'   => count($created),
            'items'   => $created,
        ], 201);
    }

    // DELETE /api/branches/{branch}/media/{id}
    public function destroy(Branch $branch, int $id)
    {
        $media = SpatieMedia::where('branch_id', $branch->id)->findOrFail($id);
        $this->authorize('delete', $media);
        $media->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function deleteBatch(\Illuminate\Http\Request $request, \App\Models\Branch $branch, string $type, int $id)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','exists:media,id'],
        ]);

        // (opcional) validar pertenencia del modelo, pero no es necesario para borrar por id + branch
        $deleted = 0;
        $items = \App\Models\Media\Media::whereIn('id', $data['ids'])
            ->where('branch_id', $branch->id)
            ->get();

        foreach ($items as $m) {
            $m->delete(); // Spatie borra archivos + registro
            $deleted++;
        }

        return response()->json(['status' => 'deleted', 'count' => $deleted]);
    }
}
