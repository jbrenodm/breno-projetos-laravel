<?php

declare(strict_types=1);

namespace Src\Fornecedores\Domain\ValueObjects;

use InvalidArgumentException;
use ramsey\uuid\Uuid;

final readonly class FornecedorId
{
    private function __construct(private string $value) {}

    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new InvalidArgumentException("O ID do fornecedor informado não é um UUIDv4 válido.");
        }

        return new self($id);
    }

    public function toString(): string
    {
        return $this->value;
    }
}