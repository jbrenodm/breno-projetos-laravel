<?php

declare(strict_types=1);

namespace Src\Responsaveis\Infrastructure\Queries;

use Src\Responsaveis\Application\Queries\ResponsaveisQuery;
use Src\Responsaveis\Domain\Funcao;
use Src\Responsaveis\Infrastructure\Persistence\ResponsavelModel;
use Src\Shared\Domain\Uuid;

final class EloquentResponsaveisQuery implements ResponsaveisQuery
{
    public function listarAtivosPorFuncao(Funcao $funcao): array
    {
        return ResponsavelModel::query()
            ->where('ativo', true)
            ->whereHas('funcoes', fn ($q) => $q->where('funcao', $funcao->value))
            ->orderBy('nome')
            ->get(['id', 'nome', 'email'])
            ->map(fn (ResponsavelModel $r) => ['id' => $r->id, 'nome' => $r->nome, 'email' => $r->email])
            ->all();
    }

    public function possuiFuncaoAtiva(string $responsavelId, Funcao $funcao): bool
    {
        if (! Uuid::ehValido($responsavelId)) {
            return false;
        }

        return ResponsavelModel::query()
            ->whereKey($responsavelId)
            ->where('ativo', true)
            ->whereHas('funcoes', fn ($q) => $q->where('funcao', $funcao->value))
            ->exists();
    }

    public function listarTodos(): array
    {
        return ResponsavelModel::query()->with('funcoes')->orderBy('nome')->get()
            ->map(fn (ResponsavelModel $r) => [
                'id' => $r->id,
                'nome' => $r->nome,
                'email' => $r->email,
                'ativo' => $r->ativo,
                'funcoes' => $r->funcoes->pluck('funcao')->sort()->values()->all(),
            ])
            ->all();
    }
}
