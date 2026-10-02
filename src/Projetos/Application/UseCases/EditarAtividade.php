<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\EditarAtividadeInput;
use Src\Projetos\Application\Ports\NomesDosResponsaveis;
use Src\Projetos\Application\Ports\VerificadorDeUsuarios;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-28 */
final readonly class EditarAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeUsuarios $usuarios,
        private NomesDosResponsaveis $nomes,
        private Relogio $relogio,
    ) {}

    public function execute(EditarAtividadeInput $input): void
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        $atividade = $projeto->buscarAtividade($input->atividadeId);

        // Só valida o papel quando o responsável muda: um AM/PV já inativo não impede corrigir outros campos.
        if ($input->accountManagerId !== $atividade->getAccountManagerId()
            && ! $this->usuarios->ehAccountManagerAtivo($input->accountManagerId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->accountManager()} informado não existe, está inativo ou não possui esse papel.");
        }

        if ($input->preVendasId !== $atividade->getPreVendasId()
            && ! $this->usuarios->ehPreVendasAtivo($input->preVendasId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->preVendas()} informado não existe, está inativo ou não possui esse papel.");
        }

        $projeto->editarAtividade(
            atividadeId: $input->atividadeId,
            descricao: $input->descricao,
            tipo: TipoAtividade::tryFrom($input->tipo)
                ?? throw new RegraDeProjetoException("Tipo de atividade inválido: {$input->tipo}."),
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
