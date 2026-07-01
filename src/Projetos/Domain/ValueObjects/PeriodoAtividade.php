<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Encapsula as regras de negócio temporais de uma Atividade.
 */
final readonly class PeriodoAtividade
{
    public function __construct(
        public DateTimeImmutable $dataEntrada,
        public DateTimeImmutable $deadline,
        public ?DateTimeImmutable $dataTermino = null
    ) {
        // Invariante: O prazo final não pode ser inventado no passado
        if ($this->deadline < $this->dataEntrada) {
            throw new InvalidArgumentException("O prazo (deadline) não pode ser anterior à data de entrada.");
        }

        // Invariante: Não é possível terminar algo antes de começar
        if ($this->dataTermino !== null && $this->dataTermino < $this->dataEntrada) {
            throw new InvalidArgumentException("A data de término não pode ser anterior à data de entrada.");
        }
    }

    /**
     * Padrão de Mutação por Substituição: Como o objeto é readonly, 
     * geramos uma nova instância com o estado alterado.
     */
    public function concluir(DateTimeImmutable $dataTermino): self
    {
        return new self(
            dataEntrada: $this->dataEntrada,
            deadline: $this->deadline,
            dataTermino: $dataTermino
        );
    }

    /**
     * Verifica se a atividade já passou do prazo estipulado.
     */
    public function estaAtrasado(DateTimeImmutable $dataComparacao): bool
    {
        $fimDasOperacoes = $this->dataTermino ?? $dataComparacao;
        
        return $fimDasOperacoes > $this->deadline;
    }
}