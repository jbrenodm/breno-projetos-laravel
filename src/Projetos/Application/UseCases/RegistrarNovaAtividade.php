<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\Ports\VerificadorDeUsuarios;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;
use Src\Shared\Application\Ports\GeradorDeId;
use Src\Shared\Application\Ports\Relogio;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-12..RN-20 */
final readonly class RegistrarNovaAtividade
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeUsuarios $usuarios,
        private GeradorDeId $geradorDeId,
        private Relogio $relogio,
    ) {}

    public function execute(RegistrarAtividadeInput $input): string
    {
        $projeto = $this->projetos->buscarPorId($input->projetoId)
            ?? throw RecursoNaoEncontradoException::para('Projeto', $input->projetoId);

        if ($input->accountManagerId !== null && ! $this->usuarios->ehAccountManagerAtivo($input->accountManagerId)) {
            throw new ResponsavelObrigatorioException('O Account Manager informado não existe, está inativo ou não possui esse papel.');
        }

        if ($input->preVendasId !== null && ! $this->usuarios->ehPreVendasAtivo($input->preVendasId)) {
            throw new ResponsavelObrigatorioException('O Pré-vendas informado não existe, está inativo ou não possui esse papel.');
        }

        $atividade = $projeto->adicionarAtividade(
            atividadeId: $this->geradorDeId->gerar(),
            descricao: $input->descricao,
            tipo: TipoAtividade::tryFrom($input->tipo)
                ?? throw new RegraDeProjetoException("Tipo de atividade inválido: {$input->tipo}."),
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
