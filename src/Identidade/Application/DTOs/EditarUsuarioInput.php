<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class EditarUsuarioInput
{
    /** @param list<string> $papeis valores de Papel */
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $usuarioId,
        public string $nome,
        public string $email,
        public array $papeis,
    ) {}
}
