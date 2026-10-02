<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\Queries;

/** Lado de leitura do contexto Parceiros (dropdowns e listagens). */
interface ParceirosQuery
{
    /** @return list<array{id: string, razao_social: string, nome_fantasia: ?string, cnpj: ?string, ativo: bool, nome: string}> */
    public function listarClientes(bool $somenteAtivos = false): array;

    /**
     * @return list<array{id: string, razao_social: string, nome_fantasia: ?string, cnpj: ?string, ativo: bool, nome: string,
     *     solucoes: list<array{id: string, nome: string, descricao: ?string, ativo: bool}>}>
     */
    public function listarFornecedores(bool $somenteAtivos = false): array;
}
