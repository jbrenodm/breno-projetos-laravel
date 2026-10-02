<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Queries;

/**
 * Lado de leitura (telas e API). Retorna arrays simples prontos para exibição.
 * Escritas NÃO passam por aqui.
 */
interface ProjetoQuery
{
    /**
     * @return list<array{id: string, cliente: string, codigo_oportunidade: ?string, status: string,
     *     fornecedores: list<string>, total_atividades: int, atividades_concluidas: int, criado_em: string}>
     */
    public function listar(): array;

    /**
     * @return ?array{id: string, cliente_id: string, cliente: string, codigo_oportunidade: ?string, status: string,
     *     fornecedores: list<array{fornecedor_id: string, fornecedor: string, solucao_id: ?string, solucao: ?string}>,
     *     atividades: list<array<string, mixed>>}
     */
    public function detalhar(string $projetoId): ?array;

    /** RN-14 @return array{account_manager_id: ?string, pre_vendas_id: ?string} */
    public function responsaveisSugeridos(string $projetoId): array;
}
