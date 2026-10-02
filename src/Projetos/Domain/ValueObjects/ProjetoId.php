<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Shared\Domain\Uuid;

/** RN-01: identificador interno do Projeto (UUID), imutável. */
final readonly class ProjetoId
{
    private function __construct(private string $valor) {}

    public static function fromString(string $valor): self
    {
        if (! Uuid::ehValido($valor)) {
            throw new RegraDeProjetoException('O identificador do projeto deve ser um UUID válido.');
        }

        return new self(strtolower($valor));
    }

    public function toString(): string
    {
        return $this->valor;
    }
}
