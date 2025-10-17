<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Province::query()->orderBy('name');

        if ($request->filled('region_id')) {
            $query->where('region_id', (int) $request->query('region_id'));
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    public function show(Request $request, int $id)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Province::query();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->findOrFail($id);
    }
}

