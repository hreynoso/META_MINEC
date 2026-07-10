<?php

namespace App\Http\Controllers;

use App\Support\Aup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Registra la aceptación del aviso de uso aceptable (A.5.10 / A.5.34).
 */
class AupController extends Controller
{
    public function accept(Request $request): RedirectResponse
    {
        Aup::accept($request->user());

        return back();
    }
}
