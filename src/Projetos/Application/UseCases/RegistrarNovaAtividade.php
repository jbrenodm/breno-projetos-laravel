<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\Ports\NomesDosResponsaveis;
use Src\Projetos\Application\Ports\VerificadorDeResponsaveis;
use Src\Projetos\Application\TiposDeAtividade;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Shared\Application\Ports\GeradorDeId;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-12..RN-20 */
final readonly class RegistrarNovaAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeResponsaveis $responsaveis,
        private NomesDosResponsaveis $nomes,
        private TiposDeAtividade $tipos,
        private GeradorDeId $geradorDeId,
        private Relogio $relogio,
    ) {}

    public function execute(RegistrarAtividadeInput $input): string
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        // RN-14: sem AM/PV e sem atividade anterior para herdar (o domínio também protege; aqui a mensagem usa os nomes atuais — RN-41).
        $sugeridos = $projeto->responsaveisSugeridos();
        if (($input->accountManagerId ?? $sugeridos['account_manager_id']) === null || ($input->preVendasId ?? $sugeridos['pre_vendas_id']) === null) {
            throw new ResponsavelObrigatorioException(
                "Para registrar a primeira atividade do projeto é obrigatório informar {$this->nomes->accountManager()} e {$this->nomes->preVendas()}."
            );
        }

        if ($input->accountManagerId !== null && ! $this->responsaveis->ehAccountManagerAtivo($input->accountManagerId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->accountManager()} informado não existe, está inativo ou não possui essa função.");
        }

        if ($input->preVendasId !== null && ! $this->responsaveis->ehPreVendasAtivo($input->preVendasId)) {
            throw new ResponsavelObrigatorioException("O {$this->nomes->preVendas()} informado não existe, está inativo ou não possui essa função.");
        }

        $this->tipos->garantirAtivoParaEscolha($input->tipoId); // RN-19/RN-43

        $atividade = $projeto->adicionarAtividade(
            atividadeId: $this->geradorDeId->gerar(),
            descricao: $input->descricao,
            tipoId: $input->tipoId,
            status: StatusAtividade::tryFrom($input->status)
                ?? throw new RegraDeProjetoException("Status de atividade inválido: {$input->status}."),
            periodo: new PeriodoAtividade($input->dataEntrada, $input->dataLimite, $input->dataInicio, $input->dataTermino),
            accountManagerId: $input->accountManagerId,
            preVendasId: $input->preVendasId,
            observacao: Observacao::opcional($input->observacao, $input->usuarioExecutorId),
            hoje: $this->relogio->hoje(),
        );

        $this->projetos->salvar($projeto);

        return $atividade->getId();
    }
}
