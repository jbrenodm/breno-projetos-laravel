<?php

use App\Http\Middleware\ExigirTrocaDeSenha;
use App\Http\Middleware\GarantirUsuarioAtivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Src\Shared\Application\AcessoNegadoException;
use Src\Shared\Application\RecursoNaoEncontradoException;
use Src\Shared\Domain\RegraDeNegocioException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ativo' => GarantirUsuarioAtivo::class,
            'troca-senha' => ExigirTrocaDeSenha::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('projetos.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Regras de negócio violadas => 422 com mensagem segura (somente para API/JSON).
        $exceptions->render(function (RegraDeNegocioException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        // A API sempre responde JSON (ex.: 401 sem token), mesmo sem o header Accept.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // RN-27: usuário autenticado sem permissão para o caso de uso => 403.
        $exceptions->render(function (AcessoNegadoException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
            }
            abort(403, $e->getMessage());
        });

        $exceptions->render(function (RecursoNaoEncontradoException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
            }
            abort(404);
        });
    })->create();
