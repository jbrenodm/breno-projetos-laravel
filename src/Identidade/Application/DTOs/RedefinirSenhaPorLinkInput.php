<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

/** O token do link já foi validado pela borda (password broker) antes de chegar aqui. */
final readonly class RedefinirSenhaPorLinkInput
{
    public function __construct(
        public string $usuarioId,
        public string $novaSenha,
    ) {}
}
