<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Entities;

use DateTimeImmutable;
use Src\Projetos\Domain\Exceptions\ObservacaoNaoPermitidaException;
use Src\Projetos\Domain\Exceptions\PeriodoInvalidoException;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\TransicaoDeStatusInvalidaException;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Shared\Domain\Uuid;

/**
 * Entidade interna do agregado Projeto. Só é criada/alterada através de Projeto.
 */
final class Atividade
{
    private const TAMANHO_MAXIMO_DESCRICAO = 2000;

    private function __construct(
        private readonly string $id,
        private string $descricao,
        private string $tipoId,
        private StatusAtividade $status,
        private PeriodoAtividade $periodo,
        private string $accountManagerId,
        private string $preVendasId,
        private ?Observacao $observacao,
    ) {}

    /**
     * Nova atividade (RN-17: pode nascer em qualquer status, inclusive Concluída, para histórico).
     */
    public static function registrar(
        string $id,
        string $descricao,
        string $tipoId,
        StatusAtividade $status,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?Observacao $observacao,
        DateTimeImmutable $hoje,
    ): self {
        self::validarId($id);
        self::validarTipo($tipoId);
        $descricao = self::validarDescricao($descricao);
        self::validarResponsavel($accountManagerId, 'Account Manager');
        self::validarResponsavel($preVendasId, 'Pré-vendas');

        $periodo = self::ajustarPeriodoAoStatus($status, $periodo, $hoje);

        return new self($id, $descricao, $tipoId, $status, $periodo, $accountManagerId, $preVendasId, $observacao);
    }

    /** Reconstrução a partir da persistência (sem reaplicar regras de criação). */
    public static function reconstituir(
        string $id,
        string $descricao,
        string $tipoId,
        StatusAtividade $status,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?Observacao $observacao,
    ): self {
        return new self($id, $descricao, $tipoId, $status, $periodo, $accountManagerId, $preVendasId, $observacao);
    }

    /**
     * RN-16 / RN-18. $data = data informada pelo usuário (início ou término); se nula usa $hoje.
     */
    public function alterarStatus(StatusAtividade $novoStatus, ?DateTimeImmutable $data, DateTimeImmutable $hoje): void
    {
        if (! $this->status->podeTransicionarPara($novoStatus)) {
            throw new TransicaoDeStatusInvalidaException(
                "Não é permitido alterar a atividade de '{$this->status->value}' para '{$novoStatus->value}'."
            );
        }

        $periodo = $this->periodo;

        // RN-18: só a concluída tem término; a não iniciada não tem início (vale ao reabrir ou voltar).
        if ($novoStatus !== StatusAtividade::CONCLUIDA) {
            $periodo = $periodo->semTermino();
        }

        if ($novoStatus === StatusAtividade::NAO_INICIADA) {
            $periodo = $periodo->semInicio();
        }

        if ($novoStatus === StatusAtividade::EM_ANDAMENTO && $periodo->dataInicio === null) {
            $periodo = $periodo->comInicio($data ?? $hoje);
        }

        if ($novoStatus === StatusAtividade::CONCLUIDA) {
            $periodo = $periodo->comTermino($data ?? $hoje);
        }

        $this->periodo = $periodo;
        $this->status = $novoStatus;
    }

    /**
     * RN-28: edita os dados da atividade (inclusive concluída). O status não muda aqui (RN-16).
     * Observação nula/vazia remove a existente; a restrição por autor segue a RN-20.
     */
    public function editar(
        string $descricao,
        string $tipoId,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?string $observacao,
        ?string $usuarioId,
        DateTimeImmutable $hoje,
    ): void {
        self::validarTipo($tipoId);
        $descricao = self::validarDescricao($descricao);
        self::validarResponsavel($accountManagerId, 'Account Manager');
        self::validarResponsavel($preVendasId, 'Pré-vendas');
        $periodo = self::ajustarPeriodoAoStatus($this->status, $periodo, $hoje);
        $novaObservacao = $this->observacaoEditada($observacao, $usuarioId);

        $this->descricao = $descricao;
        $this->tipoId = $tipoId;
        $this->periodo = $periodo;
        $this->accountManagerId = $accountManagerId;
        $this->preVendasId = $preVendasId;
        $this->observacao = $novaObservacao;
    }

    /** RN-20 */
    public function registrarObservacao(string $texto, ?string $usuarioId): void
    {
        if ($this->observacao !== null && ! $this->observacao->podeSerEditadaPor($usuarioId)) {
            throw new ObservacaoNaoPermitidaException('Somente o autor pode alterar a observação desta atividade.');
        }

        $this->observacao = new Observacao($texto, $this->observacao?->autorId ?? $usuarioId);
    }

    private function observacaoEditada(?string $texto, ?string $usuarioId): ?Observacao
    {
        $atual = $this->observacao;
        $nova = Observacao::opcional($texto, $atual?->autorId ?? $usuarioId);

        if ($nova?->texto === $atual?->texto) {
            return $atual;
        }

        if ($atual !== null && ! $atual->podeSerEditadaPor($usuarioId)) {
            throw new ObservacaoNaoPermitidaException('Somente o autor pode alterar a observação desta atividade.');
        }

        return $nova;
    }

    private static function ajustarPeriodoAoStatus(StatusAtividade $status, PeriodoAtividade $periodo, DateTimeImmutable $hoje): PeriodoAtividade
    {
        if ($status === StatusAtividade::NAO_INICIADA && $periodo->dataInicio !== null) {
            throw new PeriodoInvalidoException('Atividade não iniciada não pode ter data de início.');
        }

        if ($status !== StatusAtividade::CONCLUIDA && $periodo->dataTermino !== null) {
            throw new PeriodoInvalidoException('Somente atividades concluídas podem ter data de término.');
        }

        if ($status === StatusAtividade::EM_ANDAMENTO && $periodo->dataInicio === null) {
            // Registrada já em andamento: considera que começou na data de entrada.
            $periodo = $periodo->comInicio($periodo->dataEntrada);
        }

        if ($status === StatusAtividade::CONCLUIDA && $periodo->dataTermino === null) {
            $termino = $hoje < $periodo->dataEntrada ? $periodo->dataEntrada : $hoje;
            $periodo = $periodo->comTermino($termino);
        }

        return $periodo;
    }

    private static function validarId(string $id): void
    {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeProjetoException('O identificador da atividade deve ser um UUID válido.');
        }
    }

    /** RN-19: a existência e a situação do tipo (RN-43) são verificadas no caso de uso. */
    private static function validarTipo(string $tipoId): void
    {
        if (! Uuid::ehValido($tipoId)) {
            throw new RegraDeProjetoException('O identificador do tipo de atividade deve ser um UUID válido.');
        }
    }

    private static function validarDescricao(string $descricao): string
    {
        $limpa = trim(strip_tags($descricao));

        if ($limpa === '') {
            throw new RegraDeProjetoException('A descrição da atividade é obrigatória.');
        }

        if (mb_strlen($limpa) > self::TAMANHO_MAXIMO_DESCRICAO) {
            throw new RegraDeProjetoException('A descrição da atividade deve ter no máximo 2000 caracteres.');
        }

        return $limpa;
    }

    private static function validarResponsavel(string $id, string $papel): void
    {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeProjetoException("O identificador do {$papel} deve ser um UUID válido.");
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getTipoId(): string
    {
        return $this->tipoId;
    }

    public function getStatus(): StatusAtividade
    {
        return $this->status;
    }

    public function getPeriodo(): PeriodoAtividade
    {
        return $this->periodo;
    }

    public function getAccountManagerId(): string
    {
        return $this->accountManagerId;
    }

    public function getPreVendasId(): string
    {
        return $this->preVendasId;
    }

    public function getObservacao(): ?Observacao
    {
        return $this->observacao;
    }

    public function estaAberta(): bool
    {
        return $this->status->estaAberta();
    }
}
