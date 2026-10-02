<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Queries;

use DateTimeImmutable;

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

    /**
     * Todas as atividades de todos os projetos, filtradas e ordenadas conforme $filtro
     * (busca = nome do cliente ou descrição da atividade).
     *
     * @return list<array{id: string, projeto_id: string, projeto_status: string, cliente: string, codigo_oportunidade: ?string,
     *     fornecedores: list<string>, descricao: string, tipo: string, status: string, data_entrada: string, data_limite: string,
     *     data_inicio: ?string, data_termino: ?string, atrasada: bool, account_manager: string, pre_vendas: string}>
     */
    public function listarAtividades(FiltroAtividades $filtro = new FiltroAtividades): array;

    /**
     * Dashboards › Painel operacional (projetos não cancelados; "aberta" = não concluída). Ver REQUISITOS.md §9, item 6.
     *
     * @return array{abertas: int, atrasadas: int, vencem_em_7_dias: int, concluidas_no_mes: int,
     *     atrasadas_por_am: list<array{id: string, nome: string, total: int}>,
     *     atrasadas_por_cliente: list<array{id: string, nome: string, total: int}>,
     *     atrasadas_por_projeto: list<array{id: string, nome: string, total: int}>,
     *     proximos_vencimentos: list<array{id: string, projeto_id: string, cliente: string, descricao: string, tipo: string,
     *         status: string, data_limite: string, dias_restantes: int, account_manager: string, pre_vendas: string}>}
     */
    public function painelOperacional(DateTimeImmutable $hoje): array;

    /** RN-14 @return array{account_manager_id: ?string, pre_vendas_id: ?string} */
    public function responsaveisSugeridos(string $projetoId): array;
}
