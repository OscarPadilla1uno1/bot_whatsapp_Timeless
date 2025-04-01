<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    /**
 * Handle an incoming authentication request.
 */
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    $user = Auth::user();

    // Redirigir según los permisos del usuario
    if ($user->hasPermissionTo('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasPermissionTo('Motorista')) {
        return redirect()->route('motorista.dashboard');
    } elseif ($user->hasPermissionTo('cocina')) {
        return redirect()->route('cocina.dashboard');
    }

    // Si el usuario no tiene permisos, cerrar sesión y mostrar error 403
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    abort(403, 'No tienes permiso para acceder a esta página.');
}


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
