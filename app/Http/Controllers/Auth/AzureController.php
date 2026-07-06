<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AzureController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'No fue posible autenticar con Office 365.');
        }

        $user = User::updateOrCreate(
            ['email' => $azureUser->getEmail()],
            [
                'name' => $azureUser->getName() ?: $azureUser->getNickname(),
                'azure_id' => $azureUser->getId(),
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
