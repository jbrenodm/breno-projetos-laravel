<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

final readonly class AlterarSituacaoTipoAtividadeInput
{
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $tipoId,
        public bool $ativo,
    ) {}
}
