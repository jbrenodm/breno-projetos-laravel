<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CodigoOportunidade
{
    // Declaramos a propriedade aqui dentro. Como a classe inteira é readonly,
    // esta propriedade também será implicitamente readonly.
    private string $value;

    public function __construct(string $value)
    {
        $cleanValue = trim($value);

        if (empty($cleanValue)) {
            throw new InvalidArgumentException("O código da oportunidade não pode ser uma string vazia.");
        }

        // Inicializamos uma única vez após a sanitização e validação
        $this->value = $cleanValue;
    }

    public function toString(): string
    {
        return $this->value;
    }
}