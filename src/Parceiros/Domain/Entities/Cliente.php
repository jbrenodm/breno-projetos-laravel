<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\Entities;

use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Domain\Uuid;

final class Cliente
{
    public function __construct(
        private readonly string $id,
        private DadosCadastrais $dados,
        private bool $ativo = true,
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeParceiroException('O identificador do cliente deve ser um UUID válido.');
        }
    }

    public function atualizarDados(DadosCadastrais $dados): void
    {
        $this->dados = $dados;
    }

    public function inativar(): void
    {
        $this->ativo = false;
    }

    public function ativar(): void
    {
        $this->ativo = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDados(): DadosCadastrais
    {
        return $this->dados;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }
}
