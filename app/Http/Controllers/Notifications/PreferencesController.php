<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Routing\Controller as BaseController;

class PreferencesController extends BaseController
{
    public function index()
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update()
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}

