<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\AdicionarSolucaoInput;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Shared\Application\Ports\GeradorDeId;
use Src\Shared\Application\RecursoNaoEncontradoException;

final readonly class AdicionarSolucao
{
    public function __construct(
        private FornecedorRepositoryInterface $fornecedores,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(AdicionarSolucaoInput $input): string
    {
        $fornecedor = $this->fornecedores->buscarPorId($input->fornecedorId)
            ?? throw RecursoNaoEncontradoException::para('Fornecedor', $input->fornecedorId);

        $solucao = $fornecedor->adicionarSolucao($this->geradorDeId->gerar(), $input->nome, $input->descricao);

        $this->fornecedores->salvar($fornecedor);

        return $solucao->getId();
    }
}
