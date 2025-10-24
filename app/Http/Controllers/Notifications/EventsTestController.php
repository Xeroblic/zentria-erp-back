<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Routing\Controller as BaseController;

class EventsTestController extends BaseController
{
    public function store()
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}

