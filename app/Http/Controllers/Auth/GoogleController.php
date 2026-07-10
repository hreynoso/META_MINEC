<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DeviceSession;
use App\Support\GoogleSso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        GoogleSso::apply();

        $driver = Socialite::driver('google');

        // Restringe el selector de cuentas al dominio de Google Workspace.
        if ($domain = config('services.google.hosted_domain')) {
            $driver->with(['hd' => $domain]);
        }

        return $driver->redirect();
    }

    public function callback(): RedirectResponse
    {
        GoogleSso::apply();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', __('messages.auth.google_failed'));
        }

        $email = $googleUser->getEmail();

        // Verificación de dominio del lado del servidor (el parámetro hd no es una
        // garantía por sí solo): solo se permite el dominio de la organización.
        $domain = config('services.google.hosted_domain');
        if ($domain && ! Str::endsWith(Str::lower((string) $email), '@'.Str::lower($domain))) {
            return redirect()->route('login')
                ->with('error', __('messages.auth.google_domain_only', ['domain' => $domain]));
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $googleUser->getName() ?: $googleUser->getNickname(),
                'google_id' => $googleUser->getId(),
            ],
        );

        if ($user->isBlocked()) {
            return redirect()->route('login')
                ->with('error', __('messages.auth.account_blocked'));
        }

        // Genera password aleatoria si el registro es nuevo (SSO puro; nunca se usa)
        if (empty($user->password)) {
            $user->forceFill(['password' => bcrypt(Str::random(40))])->save();
        }

        // A.8.3 — least privilege: los usuarios SSO sin rol reciben el rol por
        // defecto (solo lectura); un administrador los eleva luego.
        $defaultRole = (string) config('security.default_role');
        if ($defaultRole !== '' && $user->roles()->count() === 0) {
            rescue(fn () => $user->assignRole($defaultRole), null, false);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        // Un solo dispositivo: si ya hay otra sesión activa, pide confirmación.
        if ($redirect = DeviceSession::resolveLogin(request(), $user)) {
            return $redirect;
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        // Libera la propiedad del dispositivo solo si esta era la sesión dueña.
        if (($user = $request->user())
            && $user->current_session_id === $request->session()->getId()) {
            DeviceSession::release($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
