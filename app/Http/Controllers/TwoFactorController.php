<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    public function showSetup(Request $request)
    {
        $user = $request->user();
        $google2fa = app(Google2FA::class);
        $secret = $user->google2fa_secret ?? $google2fa->generateSecretKey();
        $qr = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );
        return Inertia::render('Profile/TwoFactor', [
            'qr' => $qr,
            'secret' => $secret,
            'enabled' => (bool) $user->google2fa_enabled,
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();
        $google2fa = app(Google2FA::class);
        $secret = $request->input('secret');
        if (!$google2fa->verifyKey($secret, $request->input('code'))) {
            return Redirect::back()->with('error', 'Código inválido');
        }
        $user->google2fa_secret = $secret;
        $user->google2fa_enabled = true;
        $user->save();
        return Redirect::route('profile.edit')->with('success', 'Autenticación de dos factores activada');
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        $user->google2fa_secret = null;
        $user->google2fa_enabled = false;
        $user->save();
        return Redirect::route('profile.edit')->with('success', 'Autenticación de dos factores desactivada');
    }
}
