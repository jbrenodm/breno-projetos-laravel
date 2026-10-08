<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

final readonly class CadastrarTipoAtividadeInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $nome,
    ) {}
}
