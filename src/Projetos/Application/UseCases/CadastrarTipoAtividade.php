<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\CadastrarTipoAtividadeInput;
use Src\Projetos\Application\TiposDeAtividade;
use Src\Projetos\Domain\Entities\TipoAtividade;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;
use Src\Shared\Application\Ports\GeradorDeId;

/** RN-43 (só Admin Geral do Sistema) */
final readonly class CadastrarTipoAtividade
{
    public function __construct(
        private TipoAtividadeRepositoryInterface $tipos,
        private TiposDeAtividade $regras,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CadastrarTipoAtividadeInput $input): string
    {
        $this->regras->garantirAdminGeral($input->usuarioExecutorId);

        $tipo = new TipoAtividade($this->geradorDeId->gerar(), $input->nome);
        $this->regras->garantirNomeLivre($tipo);

        $this->tipos->salvar($tipo);

        return $tipo->getId();
    }
}
