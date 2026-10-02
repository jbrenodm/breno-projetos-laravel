<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Queries;

use App\Models\User;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Domain\Papel;
use Src\Shared\Domain\Uuid;

final class EloquentUsuariosQuery implements UsuariosQuery
{
    public function listarAtivosPorPapel(Papel $papel): array
    {
        return User::query()
            ->where('ativo', true)
            ->whereHas('roles', fn ($q) => $q->where('nome', $papel->value))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'nome' => $u->name, 'email' => $u->email])
            ->all();
    }

    public function possuiPapelAtivo(string $usuarioId, Papel $papel): bool
    {
        if (! Uuid::ehValido($usuarioId)) {
            return false;
        }

        return User::query()
            ->whereKey($usuarioId)
            ->where('ativo', true)
            ->whereHas('roles', fn ($q) => $q->where('nome', $papel->value))
            ->exists();
    }
}
