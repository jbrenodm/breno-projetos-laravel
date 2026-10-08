<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Queries;

interface TiposAtividadeQuery
{
    /** Opções para novas atividades e filtros. @return list<array{id: string, nome: string}> */
    public function listarAtivos(): array;

    /** Tela de tipos (Admin). @return list<array{id: string, nome: string, ativo: bool, atividades: int}> */
    public function listarTodos(): array;

    /** API: o campo "tipo" recebe o nome atual (sem diferenciar maiúsculas). */
    public function idPorNome(string $nome): ?string;
}
