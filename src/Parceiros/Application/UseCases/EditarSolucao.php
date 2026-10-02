<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\EditarSolucaoInput;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-32 (com RN-23) */
final readonly class EditarSolucao
{
    public function __construct(private FornecedorRepositoryInterface $fornecedores) {}

    public function execute(EditarSolucaoInput $input): void
    {
        $fornecedor = $this->fornecedores->buscarPorId($input->fornecedorId)
            ?? throw RecursoNaoEncontradoException::para('Fornecedor', $input->fornecedorId);

        $fornecedor->editarSolucao($input->solucaoId, $input->nome, $input->descricao);

        $this->fornecedores->salvar($fornecedor);
    }
}
