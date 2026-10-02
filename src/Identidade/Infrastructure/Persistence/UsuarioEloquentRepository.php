<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Identidade\Domain\Entities\Usuario;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\Email;
use Src\Shared\Domain\Uuid;

final class UsuarioEloquentRepository implements UsuarioRepositoryInterface
{
    public function buscarPorId(string $id): ?Usuario
    {
        if (! Uuid::ehValido($id)) {
            return null;
        }

        $user = User::query()->with('roles')->find($id);

        return $user === null ? null : Usuario::reconstituir(
            id: $user->id,
            nome: $user->name,
            email: new Email($user->email),
            papeis: $user->roles->map(fn (RoleModel $r) => Papel::from($r->nome))->values()->all(),
            senhaHash: $user->password,
            ativo: $user->ativo,
            deveTrocarSenha: $user->deve_trocar_senha,
        );
    }

    public function emailEmUso(string $email, ?string $ignorarId = null): bool
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->exists();
    }

    public function contarAdminsGeraisAtivos(?string $ignorarId = null): int
    {
        return User::query()
            ->where('ativo', true)
            ->whereHas('roles', fn ($q) => $q->where('nome', Papel::ADMIN_GERAL->value))
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->count();
    }

    /** Sem mass assignment do request: o array de persistência é montado aqui (REQUISITOS.md §8). */
    public function salvar(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario): void {
            $user = User::query()->findOrNew($usuario->getId());
            $user->id = $usuario->getId();
            $user->forceFill([
                'name' => $usuario->getNome(),
                'email' => $usuario->getEmail()->valor,
                'password' => $usuario->getSenhaHash(), // já é hash: o cast "hashed" não refaz
                'ativo' => $usuario->isAtivo(),
                'deve_trocar_senha' => $usuario->deveTrocarSenha(),
            ])->save();

            // Os papéis são definidos pelo enum Papel (RN-25): garante a linha em "roles" em vez de descartar em silêncio.
            $user->roles()->sync(array_map(
                fn (Papel $p) => RoleModel::garantir($p)->id,
                $usuario->getPapeis(),
            ));
        });
    }
}
