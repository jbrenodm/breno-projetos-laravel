<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\DTOs;

/** RN-31: ativar (true) ou inativar (false) um cliente ou fornecedor. */
final readonly class AlterarSituacaoInput
{
    public function __construct(
        public string $id,
        public bool $ativo,
    ) {}
}
