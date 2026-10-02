<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class CriarPrimeiroAdminInput
{
    public function __construct(
        public string $nome,
        public string $email,
        public string $senha,
    ) {}
}
