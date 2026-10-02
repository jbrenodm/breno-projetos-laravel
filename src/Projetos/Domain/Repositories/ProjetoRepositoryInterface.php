<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Repositories;

use Src\Projetos\Domain\Entities\Projeto;

interface ProjetoRepositoryInterface
{
    public function buscarPorId(string $id): ?Projeto;

    public function salvar(Projeto $projeto): void;
}
