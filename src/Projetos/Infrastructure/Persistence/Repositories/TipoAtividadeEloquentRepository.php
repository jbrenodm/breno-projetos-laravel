<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Repositories;

use Src\Projetos\Domain\Entities\TipoAtividade;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\TipoAtividadeModel;
use Src\Shared\Domain\Uuid;

final class TipoAtividadeEloquentRepository implements TipoAtividadeRepositoryInterface
{
    public function buscarPorId(string $id): ?TipoAtividade
    {
        if (! Uuid::ehValido($id)) {
            return null;
        }

        $model = TipoAtividadeModel::query()->find($id);

        return $model === null ? null : new TipoAtividade($model->id, $model->nome, $model->ativo);
    }

    public function nomeEmUso(string $nome, ?string $ignorarId = null): bool
    {
        return TipoAtividadeModel::query()
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->exists();
    }

    public function contarAtivos(?string $ignorarId = null): int
    {
        return TipoAtividadeModel::query()
            ->where('ativo', true)
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->count();
    }

    public function salvar(TipoAtividade $tipo): void
    {
        TipoAtividadeModel::query()->updateOrCreate(
            ['id' => $tipo->getId()],
            ['nome' => $tipo->getNome(), 'ativo' => $tipo->isAtivo()],
        );
    }
}
