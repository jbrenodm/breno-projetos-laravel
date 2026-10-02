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
use Src\Projetos\Domain\ValueObjects\TipoAtividade;
use Src\Shared\Domain\Uuid;

/**
 * Entidade interna do agregado Projeto. Só é criada/alterada através de Projeto.
 */
final class Atividade
{
    private const TAMANHO_MAXIMO_DESCRICAO = 2000;

    private function __construct(
        private readonly string $id,
        private readonly string $descricao,
        private readonly TipoAtividade $tipo,
        private StatusAtividade $status,
        private PeriodoAtividade $periodo,
        private readonly string $accountManagerId,
        private readonly string $preVendasId,
        private ?Observacao $observacao,
    ) {}

    /**
     * Nova atividade (RN-17: pode nascer em qualquer status, inclusive Concluída, para histórico).
     */
    public static function registrar(
        string $id,
        string $descricao,
        TipoAtividade $tipo,
        StatusAtividade $status,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?Observacao $observacao,
        DateTimeImmutable $hoje,
    ): self {
        self::validarId($id);
        $descricao = self::validarDescricao($descricao);
        self::validarResponsavel($accountManagerId, 'Account Manager');
        self::validarResponsavel($preVendasId, 'Pré-vendas');

        $periodo = self::ajustarPeriodoAoStatus($status, $periodo, $hoje);

        return new self($id, $descricao, $tipo, $status, $periodo, $accountManagerId, $preVendasId, $observacao);
    }

    /** Reconstrução a partir da persistência (sem reaplicar regras de criação). */
    public static function reconstituir(
        string $id,
        string $descricao,
        TipoAtividade $tipo,
        StatusAtividade $status,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?Observacao $observacao,
    ): self {
        return new self($id, $descricao, $tipo, $status, $periodo, $accountManagerId, $preVendasId, $observacao);
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

        if ($novoStatus === StatusAtividade::EM_ANDAMENTO && $periodo->dataInicio === null) {
            $periodo = $periodo->comInicio($data ?? $hoje);
        }

        if ($novoStatus === StatusAtividade::CONCLUIDA) {
            $periodo = $periodo->comTermino($data ?? $hoje);
        }

        $this->periodo = $periodo;
        $this->status = $novoStatus;
    }

    /** RN-20 */
    public function registrarObservacao(string $texto, ?string $usuarioId): void
    {
        if ($this->observacao !== null && ! $this->observacao->podeSerEditadaPor($usuarioId)) {
            throw new ObservacaoNaoPermitidaException('Somente o autor pode alterar a observação desta atividade.');
        }

        $this->observacao = new Observacao($texto, $this->observacao?->autorId ?? $usuarioId);
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

    public function getTipo(): TipoAtividade
    {
        return $this->tipo;
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
