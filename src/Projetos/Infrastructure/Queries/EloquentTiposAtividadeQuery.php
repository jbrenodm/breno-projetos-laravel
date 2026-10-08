<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Queries;

use Src\Projetos\Application\Queries\TiposAtividadeQuery;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\AtividadeModel;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\TipoAtividadeModel;

final class EloquentTiposAtividadeQuery implements TiposAtividadeQuery
{
    public function listarAtivos(): array
    {
        return TipoAtividadeModel::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
            ->map(fn (TipoAtividadeModel $t) => ['id' => $t->id, 'nome' => $t->nome])
            ->all();
    }

    public function listarTodos(): array
    {
        $uso = AtividadeModel::query()->groupBy('tipo_id')->selectRaw('tipo_id, COUNT(*) as total')->pluck('total', 'tipo_id');

        return TipoAtividadeModel::query()->orderBy('nome')->get()
            ->map(fn (TipoAtividadeModel $t) => [
                'id' => $t->id,
                'nome' => $t->nome,
                'ativo' => $t->ativo,
                'atividades' => (int) ($uso[$t->id] ?? 0),
            ])
            ->all();
    }

    public function idPorNome(string $nome): ?string
    {
        return TipoAtividadeModel::query()->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($nome))])->value('id');
    }
}
