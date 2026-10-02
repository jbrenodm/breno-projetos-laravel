<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\Repositories;

use Src\Identidade\Domain\Entities\Usuario;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(string $id): ?Usuario;

    public function emailEmUso(string $email, ?string $ignorarId = null): bool;

    /** RN-39 */
    public function contarAdminsGeraisAtivos(?string $ignorarId = null): int;

    public function salvar(Usuario $usuario): void;
}
