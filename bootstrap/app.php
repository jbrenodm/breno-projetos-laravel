<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Regras de negócio violadas => 422 com mensagem segura (somente para API/JSON).
        $exceptions->render(function (RegraDeNegocioException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        $exceptions->render(function (RecursoNaoEncontradoException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
            }
            abort(404);
        });
    })->create();
