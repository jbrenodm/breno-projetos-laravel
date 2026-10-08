<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Adapters;

use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Projetos\Application\Ports\PermissaoDeAdmin;

final readonly class PermissaoDeAdminViaIdentidade implements PermissaoDeAdmin
{
    public function __construct(private UsuarioRepositoryInterface $usuarios) {}

    public function ehAdminGeralAtivo(?string $usuarioId): bool
    {
        $usuario = $usuarioId === null ? null : $this->usuarios->buscarPorId($usuarioId);

        return $usuario !== null && $usuario->ehAdminGeralAtivo();
    }
}
