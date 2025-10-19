<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Eager-load whitelist for Region.
     * Ajusta según tus relaciones reales del modelo Region.
     * Ejemplos típicos en Chile: Region -> provinces -> communes
     */
    private const ALLOWED_WITH = [
        'provinces',
        'provinces.communes',
        // 'communes', // habilítalo solo si tienes hasManyThrough en Region
    ];

    private function parseWith(Request $request): array
    {
        return collect(explode(',', (string) $request->query('with')))
            ->map('trim')
            ->filter()            // elimina vacíos
            ->unique()            // evita repetidos
            ->filter(fn ($rel) => in_array($rel, self::ALLOWED_WITH, true))
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $with = $this->parseWith($request);

        $query = Region::query()->orderBy('geographic_order');

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    public function show(Request $request, int $id)
    {
        $with = $this->parseWith($request);

        $query = Region::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }
}
