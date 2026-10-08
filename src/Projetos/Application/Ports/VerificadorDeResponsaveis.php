<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Ports;

/**
 * Pergunta ao contexto Responsáveis se um responsável ativo possui a função exigida (RN-13, RN-42).
 */
interface VerificadorDeResponsaveis
{
    public function ehAccountManagerAtivo(string $responsavelId): bool;

    public function ehPreVendasAtivo(string $responsavelId): bool;
}
