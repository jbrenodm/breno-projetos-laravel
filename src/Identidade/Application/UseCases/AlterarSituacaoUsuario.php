<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\AlterarSituacaoUsuarioInput;
use Src\Identidade\Application\Ports\AcessosDoUsuario;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;

/** RN-33/RN-34/RN-35/RN-39 (só Admin Geral do Sistema). Inativar revoga tokens e encerra sessões. */
final readonly class AlterarSituacaoUsuario
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
        private AcessosDoUsuario $acessos,
    ) {}

    public function execute(AlterarSituacaoUsuarioInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);
        $usuario = $this->autorizacao->buscar($input->usuarioId);

        if ($input->ativo) {
            $usuario->ativar();
        } else {
            $this->autorizacao->garantirQueRestaOutroAdmin($usuario);
            $usuario->inativar();
        }

        $this->usuarios->salvar($usuario);

        if (! $input->ativo) {
            $this->acessos->revogarTudo($usuario->getId());
        }
    }
}
