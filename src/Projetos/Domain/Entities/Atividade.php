<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Entities;

use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use DateTimeImmutable;

final class Atividade
{
    public function __construct(
        private readonly string $id,
        private readonly string $descricao,
        private StatusAtividade $status,
        private PeriodoAtividade $periodo,
        private string $accountManagerId,
        private string $preVendasId
    ) {}

    /**
     * Regra de Negócio: Concluir de forma independente esta linha de trabalho.
     */
    public function concluir(DateTimeImmutable $dataTermino): void
    {
        $this->status = StatusAtividade::CONCLUIDA;
        $this->periodo = $this->periodo->concluir($dataTermino);
    }

    // Getters limpos para o Mapper e Casos de Uso exporem o estado
    public function getId(): string 
    {
        return $this->id;
    }

    public function getDescricao(): string 
    {
        return $this->descricao;
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
}