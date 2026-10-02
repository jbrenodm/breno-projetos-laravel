<?php

declare(strict_types=1);

namespace Src\Shared\Application\Ports;

use DateTimeImmutable;

/**
 * Porta para obter a data atual. Permite congelar o tempo nos testes.
 */
interface Relogio
{
    /** Data de hoje, sem horário (00:00:00), no fuso da aplicação. */
    public function hoje(): DateTimeImmutable;
}
