<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Repositories;

use Src\Projetos\Domain\Entities\TipoAtividade;

interface TipoAtividadeRepositoryInterface
{
    public function buscarPorId(string $id): ?TipoAtividade;

    /** RN-43: sem diferenciar maiúsculas. */
    public function nomeEmUso(string $nome, ?string $ignorarId = null): bool;

    public function contarAtivos(?string $ignorarId = null): int;

    public function salvar(TipoAtividade $tipo): void;
}
