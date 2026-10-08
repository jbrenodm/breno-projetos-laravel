<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\UseCases;

use Src\Responsaveis\Application\AutorizacaoDeResponsaveis;
use Src\Responsaveis\Application\DTOs\AlterarSituacaoResponsavelInput;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;

/** RN-42 (só Admin Geral do Sistema): o inativo some das listas de AM/PV; atividades existentes não mudam (RN-24). */
final readonly class AlterarSituacaoResponsavel
{
    public function __construct(
        private ResponsavelRepositoryInterface $responsaveis,
        private AutorizacaoDeResponsaveis $autorizacao,
    ) {}

    public function execute(AlterarSituacaoResponsavelInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);
        $responsavel = $this->autorizacao->buscar($input->responsavelId);

        $input->ativo ? $responsavel->ativar() : $responsavel->inativar();

        $this->responsaveis->salvar($responsavel);
    }
}
