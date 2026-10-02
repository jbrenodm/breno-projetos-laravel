<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\EditarFornecedorInput;
use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\Repositories\FornecedorRepositoryInterface;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-30 (com RN-21/RN-22) */
final readonly class EditarFornecedor
{
    public function __construct(private FornecedorRepositoryInterface $fornecedores) {}

    public function execute(EditarFornecedorInput $input): void
    {
        $fornecedor = $this->fornecedores->buscarPorId($input->id)
            ?? throw RecursoNaoEncontradoException::para('Fornecedor', $input->id);

        $cnpj = Cnpj::opcional($input->cnpj);
        if ($cnpj !== null && $this->fornecedores->cnpjEmUso($cnpj->valor, ignorarId: $input->id)) {
            throw new RegraDeParceiroException('Já existe um fornecedor cadastrado com este CNPJ.');
        }

        $fornecedor->atualizarDados(new DadosCadastrais($input->razaoSocial, $input->nomeFantasia, $cnpj));

        $this->fornecedores->salvar($fornecedor);
    }
}
