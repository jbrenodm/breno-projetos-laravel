<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class VinculoFornecedor
{
    public function __construct(
        public string $fornecedorId,
        public ?string $solucaoId = null
    ) {
        if (empty(trim($this->fornecedorId))) {
            throw new InvalidArgumentException("O identificador do fornecedor é obrigatório.");
        }
    }
}