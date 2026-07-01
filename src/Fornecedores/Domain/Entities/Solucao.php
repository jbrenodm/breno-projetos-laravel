<?php

declare(strict_types=1);

namespace Src\Fornecedores\Domain\Entities;

use Src\Fornecedores\Domain\ValueObjects\SolucaoId;
use InvalidArgumentException;

final class Solucao
{
    public function __construct(
        private readonly SolucaoId $id,
        private string $nome,
        private bool $ativa = true
    ) {
        if (empty(trim($this->nome))) {
            throw new InvalidArgumentException("O nome da solução não pode ser vazio.");
        }
    }

    public function getId(): string { return $this->id->toString(); }
    public function getNome(): string { return $this->nome; }
    public function isAtiva(): bool { return $this->ativa; }
}