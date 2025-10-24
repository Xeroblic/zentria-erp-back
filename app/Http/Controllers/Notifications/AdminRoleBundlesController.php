<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Routing\Controller as BaseController;

class AdminRoleBundlesController extends BaseController
{
    public function show(string $roleId)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update(string $roleId)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}

