<?php

declare(strict_types=1);

namespace Src\Fornecedores\Application\UseCases;

use Src\Fornecedores\Application\DTOs\CadastrarFornecedorInput;
use Src\Fornecedores\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Fornecedores\Domain\Entities\Fornecedor;
use Src\Fornecedores\Domain\ValueObjects\FornecedorId;
use Illuminate\Support\Str;

final readonly class CadastrarFornecedor
{
    public function __construct(
        private FornecedorRepositoryInterface $fornecedorRepository
    ) {}

    public function execute(CadastrarFornecedorInput $input): string
    {
        // 1. Instancia a Raiz do Agregado com um novo UUIDv4 válido
        $fornecedorId = FornecedorId::fromString(Str::uuid()->toString());
        
        $fornecedor = new Fornecedor(
            id: $fornecedorId,
            nomeFantasia: $input->nomeFantasia
        );

        // 2. Cadastra as soluções iniciais utilizando as regras de negócio da entidade
        foreach ($input->solucoes as $nomeSolucao) {
            $solucaoId = Str::uuid()->toString();
            $fornecedor->cadastrarSolucao($solucaoId, $nomeSolucao);
        }

        // 3. Persiste o Agregado completo (mestre-detalhe) de forma atómica
        $this->fornecedorRepository->save($fornecedor);

        return $fornecedor->getId();
    }
}