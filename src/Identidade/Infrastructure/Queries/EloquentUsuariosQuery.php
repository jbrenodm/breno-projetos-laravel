<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Queries;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Shared\Domain\Uuid;

final class EloquentUsuariosQuery implements UsuariosQuery
{
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
}
