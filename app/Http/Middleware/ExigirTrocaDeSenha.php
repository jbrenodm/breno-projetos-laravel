<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** RN-36: com senha temporária, o usuário só acessa a tela de troca de senha (e o logout). */
final class ExigirTrocaDeSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->deve_trocar_senha) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(
                    ['success' => false, 'error' => 'Troque a senha temporária no sistema antes de usar a API.'],
                    Response::HTTP_FORBIDDEN,
                );
            }

            return redirect()->route('senha.trocar');
        }

        return $next($request);
    }
}
