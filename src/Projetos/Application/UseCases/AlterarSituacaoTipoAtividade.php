<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\AlterarSituacaoTipoAtividadeInput;
use Src\Projetos\Application\TiposDeAtividade;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;

/** RN-43 (só Admin Geral do Sistema): atividades existentes não mudam; sempre resta um tipo ativo. */
final readonly class AlterarSituacaoTipoAtividade
{
    public function __construct(
        private TipoAtividadeRepositoryInterface $tipos,
        private TiposDeAtividade $regras,
    ) {}

    public function execute(AlterarSituacaoTipoAtividadeInput $input): void
    {
        $this->regras->garantirAdminGeral($input->usuarioExecutorId);
        $tipo = $this->regras->buscar($input->tipoId);

        if ($input->ativo) {
            $tipo->ativar();
        } else {
            $this->regras->garantirQueRestaOutroAtivo($tipo);
            $tipo->inativar();
        }

        $this->tipos->salvar($tipo);
    }
}
