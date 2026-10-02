<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class TrocarSenhaInput
{
    public function __construct(
        public string $usuarioId,
        public string $senhaAtual,
        public string $novaSenha,
    ) {}
}
