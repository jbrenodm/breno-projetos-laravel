<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\DTOs;

final readonly class EditarSolucaoInput
{
    public function __construct(
        public string $fornecedorId,
        public string $solucaoId,
        public string $nome,
        public ?string $descricao = null,
    ) {}
}
