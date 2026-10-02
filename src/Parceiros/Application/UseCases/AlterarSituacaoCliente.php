<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\UseCases;

use Src\Parceiros\Application\DTOs\AlterarSituacaoInput;
use Src\Parceiros\Domain\Repositories\ClienteRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-31: projetos existentes não são alterados (RN-24). */
final readonly class AlterarSituacaoCliente
{
    public function __construct(private ClienteRepositoryInterface $clientes) {}

    public function execute(AlterarSituacaoInput $input): void
    {
        $cliente = $this->clientes->buscarPorId($input->id)
            ?? throw RecursoNaoEncontradoException::para('Cliente', $input->id);

        $input->ativo ? $cliente->ativar() : $cliente->inativar();

        $this->clientes->salvar($cliente);
    }
}
