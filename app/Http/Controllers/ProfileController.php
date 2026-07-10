<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** Actualiza la foto de perfil del usuario autenticado. */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ], [], ['photo' => 'foto de perfil']);

        $user = $request->user();

        // Elimina la foto anterior para no dejar archivos huérfanos.
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('photo')->store('avatars', 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        return back()->with('success', __('messages.profile.photo_updated'));
    }

    /** Quita la foto de perfil del usuario autenticado. */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();

        return back()->with('success', __('messages.profile.photo_removed'));
    }
}
