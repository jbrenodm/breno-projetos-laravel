<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\AlterarClienteDoProjetoInput;
use Src\Projetos\Application\Ports\VerificadorDeParceiros;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-29 (com RN-06: o novo cliente deve estar ativo) */
final readonly class AlterarClienteDoProjeto
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeParceiros $parceiros,
    ) {}

    public function execute(AlterarClienteDoProjetoInput $input): void
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        if ($input->clienteId !== $projeto->getClienteId() && ! $this->parceiros->clienteEstaAtivo($input->clienteId)) {
            throw new RegraDeProjetoException('O cliente informado não existe ou está inativo.');
        }

        $projeto->alterarCliente($input->clienteId);

        $this->projetos->salvar($projeto);
    }
}
