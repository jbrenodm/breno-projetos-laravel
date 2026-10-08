<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RenomearTipoAtividadeInput;
use Src\Projetos\Application\TiposDeAtividade;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;

/** RN-43 (só Admin Geral do Sistema): o novo nome vale para as atividades existentes também. */
final readonly class RenomearTipoAtividade
{
    public function __construct(
        private TipoAtividadeRepositoryInterface $tipos,
        private TiposDeAtividade $regras,
    ) {}

    public function execute(RenomearTipoAtividadeInput $input): void
    {
        $this->regras->garantirAdminGeral($input->usuarioExecutorId);
        $tipo = $this->regras->buscar($input->tipoId);

        $tipo->renomear($input->nome);
        $this->regras->garantirNomeLivre($tipo, ignorarId: $tipo->getId());

        $this->tipos->salvar($tipo);
    }
}
