<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function index(Request $request)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Commune::query()->orderBy('name');

        if ($request->filled('province_id')) {
            $query->where('province_id', (int) $request->query('province_id'));
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    public function show(Request $request, int $id)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Commune::query();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->findOrFail($id);
    }
}

