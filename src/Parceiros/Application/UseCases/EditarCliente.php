<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\EditarClienteInput;
use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-30 (com RN-21/RN-22) */
final readonly class EditarCliente
{
    public function __construct(private ClienteRepositoryInterface $clientes) {}

    public function execute(EditarClienteInput $input): void
    {
        $cliente = $this->clientes->buscarPorId($input->id)
            ?? throw RecursoNaoEncontradoException::para('Cliente', $input->id);

        $cnpj = Cnpj::opcional($input->cnpj);
        if ($cnpj !== null && $this->clientes->cnpjEmUso($cnpj->valor, ignorarId: $input->id)) {
            throw new RegraDeParceiroException('Já existe um cliente cadastrado com este CNPJ.');
        }

        $cliente->atualizarDados(new DadosCadastrais($input->razaoSocial, $input->nomeFantasia, $cnpj));

        $this->clientes->salvar($cliente);
    }
}
