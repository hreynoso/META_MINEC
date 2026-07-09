<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GoogleSso;
use Illuminate\Http\RedirectResponse;
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
                ->with('error', 'No fue posible autenticar con Google Workspace.');
        }

        $email = $googleUser->getEmail();

        // Verificación de dominio del lado del servidor (el parámetro hd no es una
        // garantía por sí solo): solo se permite el dominio de la organización.
        $domain = config('services.google.hosted_domain');
        if ($domain && ! Str::endsWith(Str::lower((string) $email), '@'.Str::lower($domain))) {
            return redirect()->route('login')
                ->with('error', 'Solo se permite el acceso con cuentas de '.$domain.'.');
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
                ->with('error', 'Su cuenta ha sido bloqueada. Contacte al administrador.');
        }

        // Genera password aleatoria si el registro es nuevo (SSO puro; nunca se usa)
        if (empty($user->password)) {
            $user->forceFill(['password' => bcrypt(Str::random(40))])->save();
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
