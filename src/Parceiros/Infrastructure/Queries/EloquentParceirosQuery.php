<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Queries;

use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\FornecedorModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\SolucaoModel;

final class EloquentParceirosQuery implements ParceirosQuery
{
    public function listarClientes(bool $somenteAtivos = false): array
    {
        return ClienteModel::query()
            ->when($somenteAtivos, fn ($q) => $q->where('ativo', true))
            ->orderByRaw('COALESCE(nome_fantasia, razao_social)')
            ->get()
            ->map(fn (ClienteModel $c) => $this->cadastro($c))
            ->all();
    }

    public function listarFornecedores(bool $somenteAtivos = false): array
    {
        return FornecedorModel::query()
            ->with(['solucoes' => fn ($q) => $q->when($somenteAtivos, fn ($q) => $q->where('ativo', true))])
            ->when($somenteAtivos, fn ($q) => $q->where('ativo', true))
            ->orderByRaw('COALESCE(nome_fantasia, razao_social)')
            ->get()
            ->map(fn (FornecedorModel $f) => $this->cadastro($f) + [
                'solucoes' => $f->solucoes->map(fn (SolucaoModel $s) => [
                    'id' => $s->id,
                    'nome' => $s->nome,
                    'descricao' => $s->descricao,
                    'ativo' => $s->ativo,
                ])->values()->all(),
            ])
            ->all();
    }

    /** @return array{id: string, razao_social: string, nome_fantasia: ?string, cnpj: ?string, ativo: bool, nome: string} */
    private function cadastro(ClienteModel|FornecedorModel $m): array
    {
        return [
            'id' => $m->id,
            'razao_social' => $m->razao_social,
            'nome_fantasia' => $m->nome_fantasia,
            'cnpj' => $m->cnpj ? (new Cnpj($m->cnpj))->formatado() : null,
            'ativo' => $m->ativo,
            'nome' => $m->nome_fantasia ?? $m->razao_social,
        ];
    }
}
