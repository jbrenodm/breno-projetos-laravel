<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\AlterarSituacaoInput;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-31: projetos existentes não são alterados (RN-24). */
final readonly class AlterarSituacaoFornecedor
{
    public function __construct(private FornecedorRepositoryInterface $fornecedores) {}

    public function execute(AlterarSituacaoInput $input): void
    {
        $fornecedor = $this->fornecedores->buscarPorId($input->id)
            ?? throw RecursoNaoEncontradoException::para('Fornecedor', $input->id);

        $input->ativo ? $fornecedor->ativar() : $fornecedor->inativar();

        $this->fornecedores->salvar($fornecedor);
    }
}
