<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\AlterarStatusAtividadeInput;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-16 / RN-18 — inclui iniciar, parar, retomar e concluir. */
final readonly class AlterarStatusAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private Relogio $relogio,
    ) {}

    public function execute(AlterarStatusAtividadeInput $input): void
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        $novoStatus = StatusAtividade::tryFrom($input->novoStatus)
            ?? throw new RegraDeProjetoException("Status de atividade inválido: {$input->novoStatus}.");

        $projeto->alterarStatusAtividade($input->atividadeId, $novoStatus, $input->data, $this->relogio->hoje());

        $this->projetos->salvar($projeto);
    }
}
