<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\AlterarSituacaoSolucaoInput;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-31: projetos existentes não são alterados (RN-24). */
final readonly class AlterarSituacaoSolucao
{
    public function __construct(private FornecedorRepositoryInterface $fornecedores) {}

    public function execute(AlterarSituacaoSolucaoInput $input): void
    {
        $fornecedor = $this->fornecedores->buscarPorId($input->fornecedorId)
            ?? throw RecursoNaoEncontradoException::para('Fornecedor', $input->fornecedorId);

        $fornecedor->alterarSituacaoDaSolucao($input->solucaoId, $input->ativa);

        $this->fornecedores->salvar($fornecedor);
    }
}
