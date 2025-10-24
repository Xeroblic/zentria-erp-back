<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class NotificationController extends BaseController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function markRead(string $id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function ack(string $id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function assign(string $id, Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}

