<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\Repositories;

use Src\Parceiros\Domain\Entities\Fornecedor;

interface FornecedorRepositoryInterface
{
    public function buscarPorId(string $id): ?Fornecedor;

    public function cnpjEmUso(string $cnpj, ?string $ignorarId = null): bool;

    public function salvar(Fornecedor $fornecedor): void;
}
