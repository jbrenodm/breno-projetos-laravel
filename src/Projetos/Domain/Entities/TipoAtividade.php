<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Entities;

use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Shared\Domain\Uuid;

/** RN-19/RN-43: tipo de atividade cadastrável (nome editável; inativo não é escolhido para novas atividades). */
final class TipoAtividade
{
    public const TAMANHO_MAXIMO_NOME = 60;

    private string $nome;

    public function __construct(
        private readonly string $id,
        string $nome,
        private bool $ativo = true,
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeProjetoException('O identificador do tipo de atividade deve ser um UUID válido.');
        }

        $this->renomear($nome);
    }

    public function renomear(string $nome): void
    {
        $nome = trim(strip_tags($nome));

        if ($nome === '' || mb_strlen($nome) > self::TAMANHO_MAXIMO_NOME) {
            throw new RegraDeProjetoException('O nome do tipo de atividade é obrigatório (máximo '.self::TAMANHO_MAXIMO_NOME.' caracteres).');
        }

        $this->nome = $nome;
    }

    public function ativar(): void
    {
        $this->ativo = true;
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

    public function isAtivo(): bool
    {
        return $this->ativo;
    }
}
