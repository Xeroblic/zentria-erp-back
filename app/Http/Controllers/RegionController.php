<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Region::query()->orderBy('geographic_order');
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->get();
    }

    public function show(Request $request, int $id)
    {
        $with = array_filter(explode(',', (string) $request->query('with')));
        $query = Region::query();
        if (!empty($with)) {
            $query->with($with);
        }
        return $query->findOrFail($id);
    }
}

