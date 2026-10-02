<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\CancelarProjetoInput;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-11 */
final readonly class CancelarProjeto
{
    public function __construct(private ProjetoRepositoryInterface $projetos) {}

    public function execute(CancelarProjetoInput $input): void
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        $projeto->cancelar();

        $this->projetos->salvar($projeto);
    }
}
