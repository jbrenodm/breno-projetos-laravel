<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\Entities;

use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\ValueObjects\Email;
use Src\Shared\Domain\Uuid;

/** RN-25, RN-33..RN-36: comum (sem papel) ou Admin Geral do Sistema; AM/PV são Responsáveis (RN-42). A senha chega aqui já como hash (o hash é feito por uma porta na Aplicação). */
final class Usuario
{
    private string $nome;

    /** @var list<Papel> */
    private array $papeis;

    /** @param list<Papel> $papeis */
    private function __construct(
        private readonly string $id,
        string $nome,
        private Email $email,
        array $papeis,
        private string $senhaHash,
        private bool $ativo,
        private bool $deveTrocarSenha,
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeIdentidadeException('O identificador do usuário deve ser um UUID válido.');
        }

        $this->nome = self::validarNome($nome);
        $this->papeis = self::validarPapeis($papeis);
    }

    /**
     * RN-35/RN-36: nasce ativo e com senha temporária (troca obrigatória no primeiro acesso).
     *
     * @param  list<Papel>  $papeis
     */
    public static function cadastrar(string $id, string $nome, Email $email, array $papeis, string $senhaTemporariaHash): self
    {
        return new self($id, $nome, $email, $papeis, $senhaTemporariaHash, true, true);
    }

    /** @param list<Papel> $papeis */
    public static function reconstituir(string $id, string $nome, Email $email, array $papeis, string $senhaHash, bool $ativo, bool $deveTrocarSenha): self
    {
        return new self($id, $nome, $email, $papeis, $senhaHash, $ativo, $deveTrocarSenha);
    }

    /** @param list<Papel> $papeis */
    public function editar(string $nome, Email $email, array $papeis): void
    {
        $this->nome = self::validarNome($nome);
        $this->email = $email;
        $this->papeis = self::validarPapeis($papeis);
    }

    public function ativar(): void
    {
        $this->ativo = true;
    }

    public function inativar(): void
    {
        $this->ativo = false;
    }

    /** RN-36: definida pelo Admin — o usuário terá de trocá-la no próximo acesso. */
    public function definirSenhaTemporaria(string $hash): void
    {
        $this->senhaHash = $hash;
        $this->deveTrocarSenha = true;
    }

    /** RN-38/RN-40: definida pelo próprio usuário (troca ou link de redefinição). */
    public function definirSenhaPropria(string $hash): void
    {
        $this->senhaHash = $hash;
        $this->deveTrocarSenha = false;
    }

    public function ehAdminGeralAtivo(): bool
    {
        return $this->ativo && $this->possuiPapel(Papel::ADMIN_GERAL);
    }

    public function possuiPapel(Papel $papel): bool
    {
        return in_array($papel, $this->papeis, true);
    }

    private static function validarNome(string $nome): string
    {
        $nome = trim(strip_tags($nome));

        if ($nome === '' || mb_strlen($nome) > 255) {
            throw new RegraDeIdentidadeException('O nome é obrigatório (máximo 255 caracteres).');
        }

        return $nome;
    }

    /**
     * @param  array<mixed>  $papeis
     * @return list<Papel>
     */
    private static function validarPapeis(array $papeis): array
    {
        $unicos = [];
        foreach ($papeis as $papel) {
            if (! $papel instanceof Papel) {
                throw new RegraDeIdentidadeException('Papel inválido.');
            }
            if ($papel !== Papel::ADMIN_GERAL) {
                throw new RegraDeIdentidadeException('Usuário só pode ter o papel de Admin Geral do Sistema. AM e PV são cadastrados em Responsáveis.');
            }
            $unicos[$papel->value] = $papel;
        }

        return array_values($unicos);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    /** @return list<Papel> */
    public function getPapeis(): array
    {
        return $this->papeis;
    }

    public function getSenhaHash(): string
    {
        return $this->senhaHash;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function deveTrocarSenha(): bool
    {
        return $this->deveTrocarSenha;
    }
}
