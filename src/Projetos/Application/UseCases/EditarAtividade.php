<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\EditarAtividadeInput;
use Src\Projetos\Application\Ports\NomesDosResponsaveis;
use Src\Projetos\Application\Ports\VerificadorDeResponsaveis;
use Src\Projetos\Application\TiposDeAtividade;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-28 */
final readonly class EditarAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeResponsaveis $responsaveis,
        private NomesDosResponsaveis $nomes,
        private TiposDeAtividade $tipos,
        private Relogio $relogio,
    ) {}

    public function execute(EditarAtividadeInput $input): void
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        $atividade = $projeto->buscarAtividade($input->atividadeId);

        // Só valida a função quando o responsável muda: um AM/PV já inativo não impede corrigir outros campos.
        if ($input->accountManagerId !== $atividade->getAccountManagerId()
            && ! $this->responsaveis->ehAccountManagerAtivo($input->accountManagerId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->accountManager()} informado não existe, está inativo ou não possui essa função.");
        }

        if ($input->preVendasId !== $atividade->getPreVendasId()
            && ! $this->responsaveis->ehPreVendasAtivo($input->preVendasId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->preVendas()} informado não existe, está inativo ou não possui essa função.");
        }

        // RN-43: um tipo que ficou inativo pode ser mantido, mas não escolhido.
        if ($input->tipoId !== $atividade->getTipoId()) {
            $this->tipos->garantirAtivoParaEscolha($input->tipoId);
        }

        $projeto->editarAtividade(
            atividadeId: $input->atividadeId,
            descricao: $input->descricao,
            tipoId: $input->tipoId,
            periodo: new PeriodoAtividade($input->dataEntrada, $input->dataLimite, $input->dataInicio, $input->dataTermino),
            accountManagerId: $input->accountManagerId,
            preVendasId: $input->preVendasId,
            observacao: $input->observacao,
            usuarioId: $input->usuarioExecutorId,
            hoje: $this->relogio->hoje(),
        );

        $this->projetos->salvar($projeto);
    }
}
