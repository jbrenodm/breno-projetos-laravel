<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\UseCases;

use Src\Responsaveis\Application\AutorizacaoDeResponsaveis;
use Src\Responsaveis\Application\DTOs\EditarResponsavelInput;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;

/** RN-42 (só Admin Geral do Sistema). Atividades existentes não mudam. */
final readonly class EditarResponsavel
{
    public function __construct(
        private ResponsavelRepositoryInterface $responsaveis,
        private AutorizacaoDeResponsaveis $autorizacao,
    ) {}

    public function execute(EditarResponsavelInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);
        $responsavel = $this->autorizacao->buscar($input->responsavelId);

        $responsavel->atualizar($input->nome, $input->email, AutorizacaoDeResponsaveis::funcoes($input->funcoes));
        $this->autorizacao->garantirEmailLivre($responsavel, ignorarId: $responsavel->getId());

        $this->responsaveis->salvar($responsavel);
    }
}
