<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\DTOs;

final readonly class CadastrarResponsavelInput
{
    /** @param list<string> $funcoes valores de Funcao (ex.: 'account_manager') */
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $nome,
        public ?string $email,
        public array $funcoes,
    ) {}
}
