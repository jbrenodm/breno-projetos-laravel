<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ProjetoId
{
    private function __construct(private string $value)
    {
        // Validação simples de formato UUIDv4
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException("O identificador do projeto deve ser um UUIDv4 válido.");
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}