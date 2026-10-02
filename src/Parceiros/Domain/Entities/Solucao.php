<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\Entities;

use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Shared\Domain\Uuid;

/** Entidade interna do agregado Fornecedor. */
final class Solucao
{
    private string $nome;

    private ?string $descricao;

    public function __construct(
        private readonly string $id,
        string $nome,
        ?string $descricao = null,
        private bool $ativo = true,
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeParceiroException('O identificador da solução deve ser um UUID válido.');
        }

        $nome = trim(strip_tags($nome));
        if ($nome === '' || mb_strlen($nome) > 255) {
            throw new RegraDeParceiroException('O nome da solução é obrigatório (máximo 255 caracteres).');
        }

        $descricao = $descricao === null ? null : trim(strip_tags($descricao));

        $this->nome = $nome;
        $this->descricao = $descricao === '' ? null : $descricao;
    }

    public function inativar(): void
    {
        $this->ativo = false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }
}
