<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', //
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // MAPEAMENTO GLOBAL DE EXCEÇÕES DE DOMÍNIO
        // Sempre que o domínio barrar uma operação por quebra de invariante,
        // o Laravel intercepta e monta a resposta JSON padronizada com HTTP 422.
        $exceptions->render(function (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    })->create();