<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Repositories;

use Src\Projetos\Domain\Entities\Projeto;

interface ProjetoRepositoryInterface
{
    public function findById(string $id): ?Projeto;

    public function save(Projeto $projeto): void;
}