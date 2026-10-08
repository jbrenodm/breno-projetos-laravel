<?php

declare(strict_types=1);

namespace Src\Responsaveis\Domain\Entities;

use Src\Responsaveis\Domain\Exceptions\RegraDeResponsavelException;
use Src\Responsaveis\Domain\Funcao;
use Src\Shared\Domain\Uuid;

/** RN-42: AM e/ou PV. Não é usuário do sistema (não entra no sistema). */
final class Responsavel
{
    private string $nome;

    private ?string $email;

    /** @var list<Funcao> */
    private array $funcoes;

    /** @param list<Funcao> $funcoes */
    public function __construct(
        private readonly string $id,
        string $nome,
        ?string $email,
        array $funcoes,
        private bool $ativo = true,
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeResponsavelException('O identificador do responsável deve ser um UUID válido.');
        }

        $this->atualizar($nome, $email, $funcoes);
    }

    /** @param list<Funcao> $funcoes */
    public function atualizar(string $nome, ?string $email, array $funcoes): void
    {
        $this->nome = self::validarNome($nome);
        $this->email = self::validarEmail($email);
        $this->funcoes = self::validarFuncoes($funcoes);
    }

    public function ativar(): void
    {
        $this->ativo = true;
    }

    public function inativar(): void
    {
        $this->ativo = false;
    }

    public function possuiFuncao(Funcao $funcao): bool
    {
        return in_array($funcao, $this->funcoes, true);
    }

    private static function validarNome(string $nome): string
    {
        $nome = trim(strip_tags($nome));

        if ($nome === '' || mb_strlen($nome) > 255) {
            throw new RegraDeResponsavelException('O nome é obrigatório (máximo 255 caracteres).');
        }

        return $nome;
    }

    /** Opcional; guardado em minúsculas para a unicidade não diferenciar maiúsculas. */
    private static function validarEmail(?string $email): ?string
    {
        $email = $email === null ? '' : mb_strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        if (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RegraDeResponsavelException('E-mail inválido.');
        }

        return $email;
    }

    /**
     * @param  array<mixed>  $funcoes
     * @return list<Funcao>
     */
    private static function validarFuncoes(array $funcoes): array
    {
        $unicas = [];
        foreach ($funcoes as $funcao) {
            if (! $funcao instanceof Funcao) {
                throw new RegraDeResponsavelException('Função inválida.');
            }
            $unicas[$funcao->value] = $funcao;
        }

        if ($unicas === []) {
            throw new RegraDeResponsavelException('O responsável deve ter pelo menos uma função.');
        }

        return array_values($unicas);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /** @return list<Funcao> */
    public function getFuncoes(): array
    {
        return $this->funcoes;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }
}
