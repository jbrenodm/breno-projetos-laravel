<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Ports;

/**
 * Pergunta ao contexto Identidade se um usuário ativo possui o papel exigido (RN-13, RN-25).
 */
interface VerificadorDeUsuarios
{
    public function ehAccountManagerAtivo(string $usuarioId): bool;

    public function ehPreVendasAtivo(string $usuarioId): bool;
}
