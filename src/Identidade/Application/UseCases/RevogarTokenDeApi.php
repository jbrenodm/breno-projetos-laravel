<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\DTOs\TokenDeApiInput;
use Src\Identidade\Application\Ports\AcessosDoUsuario;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-34/RN-40 + RN-27: só revoga token do próprio usuário. */
final readonly class RevogarTokenDeApi
{
    public function __construct(private AcessosDoUsuario $acessos) {}

    public function execute(TokenDeApiInput $input): void
    {
        if (! $this->acessos->revogarToken($input->usuarioId, $input->valor)) {
            throw RecursoNaoEncontradoException::para('Token', $input->valor);
        }
    }
}
