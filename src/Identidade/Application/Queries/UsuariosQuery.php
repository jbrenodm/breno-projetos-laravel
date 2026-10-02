<?php

declare(strict_types=1);

namespace Src\Identidade\Application\Queries;

use Src\Identidade\Domain\Papel;

interface UsuariosQuery
{
    /** @return list<array{id: string, nome: string, email: string}> usuários ativos com o papel */
    public function listarAtivosPorPapel(Papel $papel): array;

    public function possuiPapelAtivo(string $usuarioId, Papel $papel): bool;
}
