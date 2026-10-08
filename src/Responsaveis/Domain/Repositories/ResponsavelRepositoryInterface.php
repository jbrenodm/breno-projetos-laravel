<?php

declare(strict_types=1);

namespace Src\Responsaveis\Domain\Repositories;

use Src\Responsaveis\Domain\Entities\Responsavel;

interface ResponsavelRepositoryInterface
{
    public function buscarPorId(string $id): ?Responsavel;

    public function emailEmUso(string $email, ?string $ignorarId = null): bool;

    public function salvar(Responsavel $responsavel): void;
}
