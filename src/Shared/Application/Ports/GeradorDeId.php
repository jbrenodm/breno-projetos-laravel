<?php

declare(strict_types=1);

namespace Src\Shared\Application\Ports;

/**
 * Porta para geração de identificadores (UUID). Implementada na Infraestrutura.
 */
interface GeradorDeId
{
    public function gerar(): string;
}
