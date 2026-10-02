<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Domain\Entities\Fornecedor;
use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Application\Ports\GeradorDeId;

final readonly class CadastrarFornecedor
{
    public function __construct(
        private FornecedorRepositoryInterface $fornecedores,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CadastrarFornecedorInput $input): string
    {
        $cnpj = Cnpj::opcional($input->cnpj);

        if ($cnpj !== null && $this->fornecedores->cnpjEmUso($cnpj->valor)) {
            throw new RegraDeParceiroException('Já existe um fornecedor cadastrado com este CNPJ.');
        }

        $fornecedor = new Fornecedor(
            $this->geradorDeId->gerar(),
            new DadosCadastrais($input->razaoSocial, $input->nomeFantasia, $cnpj),
        );

        foreach ($input->solucoes as $nome) {
            if (trim($nome) !== '') {
                $fornecedor->adicionarSolucao($this->geradorDeId->gerar(), $nome);
            }
        }

        $this->fornecedores->salvar($fornecedor);

        return $fornecedor->getId();
    }
}
