<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Mappers;

use Src\Parceiros\Domain\Entities\Cliente;
use Src\Parceiros\Domain\Entities\Fornecedor;
use Src\Parceiros\Domain\Entities\Solucao;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\FornecedorModel;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\SolucaoModel;

final class ParceirosMapper
{
    public static function clienteParaDominio(ClienteModel $model): Cliente
    {
        return new Cliente($model->id, self::dados($model->razao_social, $model->nome_fantasia, $model->cnpj), $model->ativo);
    }

    public static function fornecedorParaDominio(FornecedorModel $model): Fornecedor
    {
        $solucoes = $model->solucoes
            ->map(fn (SolucaoModel $s) => new Solucao($s->id, $s->nome, $s->descricao, $s->ativo))
            ->values()
            ->all();

        return new Fornecedor($model->id, self::dados($model->razao_social, $model->nome_fantasia, $model->cnpj), $model->ativo, $solucoes);
    }

    /** @return array<string, mixed> */
    public static function dadosParaPersistencia(DadosCadastrais $dados, bool $ativo): array
    {
        return [
            'razao_social' => $dados->razaoSocial,
            'nome_fantasia' => $dados->nomeFantasia,
            'cnpj' => $dados->cnpj?->valor,
            'ativo' => $ativo,
        ];
    }

    private static function dados(string $razao, ?string $fantasia, ?string $cnpj): DadosCadastrais
    {
        return new DadosCadastrais($razao, $fantasia, Cnpj::opcional($cnpj));
    }
}
