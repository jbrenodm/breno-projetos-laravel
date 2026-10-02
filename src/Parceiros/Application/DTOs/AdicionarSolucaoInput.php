<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\DTOs;

final readonly class AdicionarSolucaoInput
{
    public function __construct(
        public string $fornecedorId,
        public string $nome,
        public ?string $descricao = null,
    ) {}
}
