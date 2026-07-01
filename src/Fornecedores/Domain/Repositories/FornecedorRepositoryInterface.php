<?php

declare(strict_types=1);

namespace Src\Fornecedores\Domain\Repositories;

use Src\Fornecedores\Domain\Entities\Fornecedor;

interface FornecedorRepositoryInterface
{
    public function save(Fornecedor $fornecedor): void;
    public function findById(string $id): ?Fornecedor;
}