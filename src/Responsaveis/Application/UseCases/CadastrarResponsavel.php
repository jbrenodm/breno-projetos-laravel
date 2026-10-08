<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\UseCases;

use Src\Responsaveis\Application\AutorizacaoDeResponsaveis;
use Src\Responsaveis\Application\DTOs\CadastrarResponsavelInput;
use Src\Responsaveis\Domain\Entities\Responsavel;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;
use Src\Shared\Application\Ports\GeradorDeId;

/** RN-42 (só Admin Geral do Sistema) */
final readonly class CadastrarResponsavel
{
    public function __construct(
        private ResponsavelRepositoryInterface $responsaveis,
        private AutorizacaoDeResponsaveis $autorizacao,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CadastrarResponsavelInput $input): string
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);

        $responsavel = new Responsavel(
            $this->geradorDeId->gerar(),
            $input->nome,
            $input->email,
            AutorizacaoDeResponsaveis::funcoes($input->funcoes),
        );
        $this->autorizacao->garantirEmailLivre($responsavel);

        $this->responsaveis->salvar($responsavel);

        return $responsavel->getId();
    }
}
