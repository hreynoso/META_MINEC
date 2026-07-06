<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function keepAlive(): JsonResponse
    {
        return response()->json(['ok' => true, 'ts' => now()->timestamp]);
    }
}
