<?php

declare(strict_types=1);

namespace Src\Identidade\Application\Ports;

/** RN-34: tokens de API (e sessões) do usuário. */
interface AcessosDoUsuario
{
    /** Cria um token de API e devolve o valor em texto puro (exibido uma única vez). */
    public function emitirToken(string $usuarioId, string $nome): string;

    /** Revoga um token do próprio usuário; retorna false se o token não for dele. */
    public function revogarToken(string $usuarioId, string $tokenId): bool;

    /** Revoga todos os tokens e encerra as sessões abertas do usuário. */
    public function revogarTudo(string $usuarioId): void;
}
