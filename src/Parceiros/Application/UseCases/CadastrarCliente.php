<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Domain\Entities\Cliente;
use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Parceiros\Domain\ValueObjects\Cnpj;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Application\Ports\GeradorDeId;

final readonly class CadastrarCliente
{
    public function __construct(
        private ClienteRepositoryInterface $clientes,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CadastrarClienteInput $input): string
    {
        $cnpj = Cnpj::opcional($input->cnpj);

        if ($cnpj !== null && $this->clientes->cnpjEmUso($cnpj->valor)) {
            throw new RegraDeParceiroException('Já existe um cliente cadastrado com este CNPJ.');
        }

        $cliente = new Cliente(
            $this->geradorDeId->gerar(),
            new DadosCadastrais($input->razaoSocial, $input->nomeFantasia, $cnpj),
        );

        $this->clientes->salvar($cliente);

        return $cliente->getId();
    }
}
