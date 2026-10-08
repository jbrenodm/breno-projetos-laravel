<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\Queries;

use Src\Responsaveis\Domain\Funcao;

interface ResponsaveisQuery
{
    /** Listas de AM/PV nas telas de atividade. @return list<array{id: string, nome: string, email: ?string}> */
    public function listarAtivosPorFuncao(Funcao $funcao): array;

    public function possuiFuncaoAtiva(string $responsavelId, Funcao $funcao): bool;

    /** Tela de responsáveis. @return list<array{id: string, nome: string, email: ?string, ativo: bool, funcoes: list<string>}> */
    public function listarTodos(): array;
}
