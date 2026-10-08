<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application;

use Src\Responsaveis\Application\Ports\PermissaoDeAdmin;
use Src\Responsaveis\Domain\Entities\Responsavel;
use Src\Responsaveis\Domain\Exceptions\RegraDeResponsavelException;
use Src\Responsaveis\Domain\Funcao;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;
use Src\Shared\Application\AcessoNegadoException;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-27/RN-42: regras compartilhadas pelos casos de uso de Responsáveis. */
final readonly class AutorizacaoDeResponsaveis
{
    public function __construct(
        private ResponsavelRepositoryInterface $responsaveis,
        private PermissaoDeAdmin $permissao,
    ) {}

    public function garantirAdminGeral(?string $usuarioExecutorId): void
    {
        if (! $this->permissao->ehAdminGeralAtivo($usuarioExecutorId)) {
            throw AcessoNegadoException::somenteAdminGeral();
        }
    }

    public function buscar(string $responsavelId): Responsavel
    {
        return $this->responsaveis->buscarPorId($responsavelId)
            ?? throw RecursoNaoEncontradoException::para('Responsável', $responsavelId);
    }

    /** RN-42: e-mail opcional, mas único entre os responsáveis quando informado. */
    public function garantirEmailLivre(Responsavel $responsavel, ?string $ignorarId = null): void
    {
        $email = $responsavel->getEmail();

        if ($email !== null && $this->responsaveis->emailEmUso($email, $ignorarId)) {
            throw new RegraDeResponsavelException('Já existe um responsável com este e-mail.');
        }
    }

    /**
     * @param  list<string>  $valores
     * @return list<Funcao>
     */
    public static function funcoes(array $valores): array
    {
        return array_map(
            fn (string $v) => Funcao::tryFrom($v) ?? throw new RegraDeResponsavelException("Função inválida: {$v}."),
            array_values($valores),
        );
    }
}
