<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\Repositories;

use Src\Parceiros\Domain\Entities\Cliente;

interface ClienteRepositoryInterface
{
    public function buscarPorId(string $id): ?Cliente;

    public function cnpjEmUso(string $cnpj, ?string $ignorarId = null): bool;

    public function salvar(Cliente $cliente): void;
}
