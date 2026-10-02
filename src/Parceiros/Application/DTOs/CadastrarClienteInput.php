<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\DTOs;

final readonly class CadastrarClienteInput
{
    public function __construct(
        public string $razaoSocial,
        public ?string $nomeFantasia = null,
        public ?string $cnpj = null,
    ) {}
}
