<?php

declare(strict_types=1);

namespace Src\Fornecedores\Domain\Entities;

use Src\Fornecedores\Domain\ValueObjects\FornecedorId;
use Src\Fornecedores\Domain\ValueObjects\SolucaoId;
use InvalidArgumentException;

final class Fornecedor
{
    /**
     * @param Solucao[] $solucoes
     */
    public function __construct(
        private readonly FornecedorId $id,
        private string $nomeFantasia,
        private bool $ativo = true,
        private array $solucoes = []
    ) {
        if (empty(trim($this->nomeFantasia))) {
            throw new InvalidArgumentException("O nome fantasia do fornecedor não pode ser vazio.");
        }
    }

    /**
     * Adiciona uma solução ao catálogo do fornecedor aplicando a proteção contra duplicidade
     */
    public function cadastrarSolucao(string $idSolucao, string $nome): void
    {
        $nomeNormalizado = mb_strtolower(trim($nome));

        foreach ($this->solucoes as $solucaoExistente) {
            if (mb_strtolower(trim($solucaoExistente->getNome())) === $nomeNormalizado) {
                throw new InvalidArgumentException("Este fornecedor já possui uma solução cadastrada com o nome '{$nome}'.");
            }
        }

        $this->solucoes[] = new Solucao(
            id: SolucaoId::fromString($idSolucao),
            nome: $nome
        );
    }

    // --- Getters e Mutadores ---
    public function getId(): string { return $this->id->toString(); }
    public function getNomeFantasia(): string { return $this->nomeFantasia; }
    public function isAtivo(): bool { return $this->ativo; }
    public function getSolucoes(): array { return $this->solucoes; }
}