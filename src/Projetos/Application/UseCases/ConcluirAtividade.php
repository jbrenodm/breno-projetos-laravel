<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\ConcluirAtividadeInput;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use InvalidArgumentException;

final readonly class ConcluirAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetoRepository
    ) {}

    public function execute(ConcluirAtividadeInput $input): void
    {
        // 1. Recupera o Agregado Root
        $projeto = $this->projetoRepository->findById($input->projetoId);

        if ($projeto === null) {
            throw new InvalidArgumentException("Projeto com ID {$input->projetoId} não foi encontrado.");
        }

        $projeto->concluirAtividade($input->atividadeId, $input->dataTermino);

        $this->projetoRepository->save($projeto);
    }
}