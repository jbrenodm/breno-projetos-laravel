<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\RedefinirSenhaTemporariaInput;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;

/** RN-36 (só Admin Geral do Sistema): o usuário terá de trocar a senha no próximo acesso. */
final readonly class RedefinirSenhaTemporaria
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
        private HashDeSenha $hash,
    ) {}

    public function execute(RedefinirSenhaTemporariaInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);
        $usuario = $this->autorizacao->buscar($input->usuarioId);

        PoliticaDeSenha::validar($input->senhaTemporaria);
        $usuario->definirSenhaTemporaria($this->hash->gerar($input->senhaTemporaria));

        $this->usuarios->salvar($usuario);
    }
}
