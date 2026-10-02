<?php

declare(strict_types=1);

namespace Src\Identidade\Application\Queries;

/** RN-41: nomes exibidos e siglas dos papéis (o que as telas mostram). */
interface PapeisQuery
{
    /** @return array<string, array{nome: string, sigla: ?string}> chave = identificador do Papel */
    public function nomes(): array;

    /** Tela de papéis. @return list<array{papel: string, nome: string, sigla: ?string, usuarios: int}> */
    public function listar(): array;
}
