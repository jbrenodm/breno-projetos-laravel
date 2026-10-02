<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

/** Gerar (valor = nome do token) ou revogar (valor = id do token) um token de API do próprio usuário. */
final readonly class TokenDeApiInput
{
    public function __construct(
        public string $usuarioId,
        public string $valor,
    ) {}
}
