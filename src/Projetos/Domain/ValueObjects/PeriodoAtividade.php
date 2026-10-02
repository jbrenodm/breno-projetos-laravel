<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use DateTimeImmutable;
use Src\Projetos\Domain\Exceptions\PeriodoInvalidoException;

/**
 * RN-18: datas da atividade (precisão de dia).
 */
final readonly class PeriodoAtividade
{
    public DateTimeImmutable $dataEntrada;

    public DateTimeImmutable $dataLimite;

    public ?DateTimeImmutable $dataInicio;

    public ?DateTimeImmutable $dataTermino;

    public function __construct(
        DateTimeImmutable $dataEntrada,
        DateTimeImmutable $dataLimite,
        ?DateTimeImmutable $dataInicio = null,
        ?DateTimeImmutable $dataTermino = null,
    ) {
        $this->dataEntrada = self::dia($dataEntrada);
        $this->dataLimite = self::dia($dataLimite);
        $this->dataInicio = $dataInicio ? self::dia($dataInicio) : null;
        $this->dataTermino = $dataTermino ? self::dia($dataTermino) : null;

        if ($this->dataLimite < $this->dataEntrada) {
            throw new PeriodoInvalidoException('A data limite não pode ser anterior à data de entrada.');
        }

        if ($this->dataInicio !== null && $this->dataInicio < $this->dataEntrada) {
            throw new PeriodoInvalidoException('A data de início não pode ser anterior à data de entrada.');
        }

        if ($this->dataTermino !== null && $this->dataTermino < $this->dataEntrada) {
            throw new PeriodoInvalidoException('A data de término não pode ser anterior à data de entrada.');
        }

        if ($this->dataTermino !== null && $this->dataInicio !== null && $this->dataTermino < $this->dataInicio) {
            throw new PeriodoInvalidoException('A data de término não pode ser anterior à data de início.');
        }
    }

    public function comInicio(DateTimeImmutable $dataInicio): self
    {
        return new self($this->dataEntrada, $this->dataLimite, $dataInicio, $this->dataTermino);
    }

    public function comTermino(DateTimeImmutable $dataTermino): self
    {
        return new self($this->dataEntrada, $this->dataLimite, $this->dataInicio, $dataTermino);
    }

    public function semTermino(): self
    {
        return new self($this->dataEntrada, $this->dataLimite, $this->dataInicio, null);
    }

    public function estaAtrasado(DateTimeImmutable $hoje): bool
    {
        return ($this->dataTermino ?? self::dia($hoje)) > $this->dataLimite;
    }

    private static function dia(DateTimeImmutable $data): DateTimeImmutable
    {
        return $data->setTime(0, 0);
    }
}
