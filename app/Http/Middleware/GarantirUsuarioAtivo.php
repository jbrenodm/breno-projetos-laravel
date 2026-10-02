<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** RN-33: usuário inativado com a sessão aberta é desconectado na requisição seguinte. */
final class GarantirUsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario !== null && ! $usuario->ativo) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Usuário inativo.'], Response::HTTP_UNAUTHORIZED);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('erro', 'Seu usuário está inativo. Procure o Admin Geral do Sistema.');
        }

        return $next($request);
    }
}
