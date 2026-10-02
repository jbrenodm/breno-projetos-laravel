<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Queries;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
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

    public function listarTodos(): array
    {
        return User::query()->with('roles')->orderBy('name')->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nome' => $u->name,
                'email' => $u->email,
                'ativo' => $u->ativo,
                'deve_trocar_senha' => $u->deve_trocar_senha,
                'papeis' => $u->roles->pluck('nome')->sort()->values()->all(),
            ])
            ->all();
    }

    public function tokensDe(string $usuarioId): array
    {
        if (! Uuid::ehValido($usuarioId)) {
            return [];
        }

        return PersonalAccessToken::query()
            ->where('tokenable_type', (new User)->getMorphClass())
            ->where('tokenable_id', $usuarioId)
            ->latest()
            ->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => (string) $t->id,
                'nome' => $t->name,
                'criado_em' => $t->created_at->format('d/m/Y H:i'),
                'ultimo_uso' => $t->last_used_at?->format('d/m/Y H:i'),
            ])
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
