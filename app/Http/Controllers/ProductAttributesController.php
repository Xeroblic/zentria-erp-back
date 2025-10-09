<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductAttributesController extends Controller
{
    public function show(Branch $branch, Product $product)
    {
        $this->authorize('view', $product);
        if ($product->branch_id !== $branch->id) abort(404);

        // Solo los atributos
        return response()->json([
            'attributes' => $product->attributes_json ?? new \stdClass(),
        ]);
    }

    /**
     * PATCH /attributes
     * Body JSON:
     * {
     *   "set": { "color": "negro", "specs.ram": 16, "tags": ["mini","silent"] },
     *   "unset": ["legacy.flag", "tmp"]
     * }
     * - "set" hace deep-merge, soporta dot-notation.
     * - "unset" elimina paths (dot-notation).
     */
    public function patch(Request $request, Branch $branch, Product $product)
    {
        if ($product->branch_id !== $branch->id) abort(404);
        $this->authorize('update', $product);

        $data = $request->validate([
            'set'   => ['sometimes','array'],
            'unset' => ['sometimes','array'],
            'unset.*' => ['string'],
        ]);

        $attrs = $product->attributes_json ?? [];

        // 1) deep-merge de claves "simples" (sin punto)
        $set = $data['set'] ?? [];
        $simple = [];
        $dotted = [];
        foreach ($set as $k => $v) {
            if (is_string($k) && str_contains($k, '.')) $dotted[$k] = $v;
            else $simple[$k] = $v;
        }
        if (!empty($simple)) {
            $attrs = array_replace_recursive($attrs, $simple);
        }

        // 2) aplica dot-notation
        foreach ($dotted as $path => $value) {
            Arr::set($attrs, $path, $value);
        }

        // 3) elimina paths si enviaron "unset"
        foreach (($data['unset'] ?? []) as $path) {
            Arr::forget($attrs, $path);
        }

        $product->attributes_json = $attrs;
        $product->save();

        return response()->json([
            'updated'    => true,
            'attributes' => $product->attributes_json,
        ]);
    }

    /**
     * DELETE /attributes
     * Acepta:
     *   ?path=specs.ram
     *   ?paths[]=a&paths[]=b
     * o JSON: { "path": "x.y", "paths": ["a","b"] }
     */
    public function destroy(Request $request, Branch $branch, Product $product)
    {
        if ($product->branch_id !== $branch->id) abort(404);
        $this->authorize('update', $product);

        $paths = [];
        if ($p = $request->query('path', $request->input('path'))) $paths[] = $p;
        $paths = array_merge($paths, (array) $request->query('paths', $request->input('paths', [])));

        if (empty($paths)) {
            return response()->json([
                'deleted' => false,
                'message' => 'Debes enviar ?path=... o paths[]',
            ], 422);
        }

        $attrs = $product->attributes_json ?? [];
        foreach ($paths as $path) {
            Arr::forget($attrs, $path);
        }

        $product->attributes_json = $attrs;
        $product->save();

        return response()->json(['deleted' => true, 'attributes' => $attrs]);
        // Si prefieres idempotencia estricta estilo REST: return response()->noContent();
    }
}
