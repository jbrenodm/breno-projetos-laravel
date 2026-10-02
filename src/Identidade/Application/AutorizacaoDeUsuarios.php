<?php

declare(strict_types=1);

namespace Src\Identidade\Application;

use Src\Identidade\Domain\Entities\Usuario;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Shared\Application\AcessoNegadoException;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-27/RN-35/RN-39: regras de autorização compartilhadas pelos casos de uso de Identidade. */
final readonly class AutorizacaoDeUsuarios
{
    public function __construct(private UsuarioRepositoryInterface $usuarios) {}

    public function garantirAdminGeral(?string $usuarioExecutorId): void
    {
        $executor = $usuarioExecutorId === null ? null : $this->usuarios->buscarPorId($usuarioExecutorId);

        if ($executor === null || ! $executor->ehAdminGeralAtivo()) {
            throw AcessoNegadoException::somenteAdminGeral();
        }
    }

    public function buscar(string $usuarioId): Usuario
    {
        return $this->usuarios->buscarPorId($usuarioId)
            ?? throw RecursoNaoEncontradoException::para('Usuário', $usuarioId);
    }

    /**
     * @param  list<string>  $valores
     * @return list<Papel>
     */
    public static function papeis(array $valores): array
    {
        return array_map(
            fn (string $v) => Papel::tryFrom($v) ?? throw new RegraDeIdentidadeException("Papel inválido: {$v}."),
            array_values($valores),
        );
    }

    /** RN-39: chamado quando $usuario deixará de ser Admin Geral do Sistema ativo. */
    public function garantirQueRestaOutroAdmin(Usuario $usuario): void
    {
        if ($usuario->ehAdminGeralAtivo() && $this->usuarios->contarAdminsGeraisAtivos(ignorarId: $usuario->getId()) === 0) {
            throw new RegraDeIdentidadeException('O sistema precisa ter pelo menos um Admin Geral do Sistema ativo.');
        }
    }
}
