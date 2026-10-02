<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Adapters;

use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Domain\Papel;
use Src\Projetos\Application\Ports\VerificadorDeUsuarios;

final readonly class VerificadorDeUsuariosViaIdentidade implements VerificadorDeUsuarios
{
    public function __construct(private UsuariosQuery $usuarios) {}

    public function ehAccountManagerAtivo(string $usuarioId): bool
    {
        return $this->usuarios->possuiPapelAtivo($usuarioId, Papel::ACCOUNT_MANAGER);
    }

    public function ehPreVendasAtivo(string $usuarioId): bool
    {
        return $this->usuarios->possuiPapelAtivo($usuarioId, Papel::PRE_VENDAS);
    }
}
