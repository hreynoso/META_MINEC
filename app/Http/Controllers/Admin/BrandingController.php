<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Branding', [
            'assets' => Branding::urls(),
            'colors' => Branding::colors(),
        ]);
    }

    public function updateColors(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sidebar' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebar_hover' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [], [
            'sidebar' => 'color del sidebar',
            'sidebar_hover' => 'color activo del sidebar',
            'brand' => 'color primario',
        ]);

        foreach (array_keys(Branding::COLORS) as $key) {
            Setting::put("branding.color.{$key}", $data[$key]);
        }

        return back()->with('success', __('messages.branding.colors_updated'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo_sidebar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'logo_login' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'login_background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,svg', 'max:1024'],
        ], [], [
            'logo_sidebar' => 'logo del sidebar',
            'logo_login' => 'logo del login',
            'login_background' => 'fondo del login',
            'favicon' => 'favicon',
        ]);

        foreach (Branding::KEYS as $key) {
            if (! $request->hasFile($key)) {
                continue;
            }

            // Elimina el archivo anterior para no dejar huérfanos.
            if ($old = Branding::path($key)) {
                Storage::disk('public')->delete($old);
            }

            $path = $request->file($key)->store('branding', 'public');
            Setting::put("branding.{$key}", $path);
        }

        return back()->with('success', __('messages.branding.updated'));
    }
}
