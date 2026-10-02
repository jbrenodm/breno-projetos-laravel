<?php

declare(strict_types=1);

namespace Src\Identidade\Application\DTOs;

final readonly class CadastrarUsuarioInput
{
    /** @param list<string> $papeis valores de Papel (ex.: 'account_manager') */
    public function __construct(
        public ?string $usuarioExecutorId,
        public string $nome,
        public string $email,
        public array $papeis,
        public string $senhaTemporaria,
    ) {}
}
