<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\RedefinirSenhaPorLinkInput;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;

/** RN-37/RN-38: nova senha escolhida pelo usuário a partir do link enviado por e-mail. */
final readonly class RedefinirSenhaPorLink
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
        private HashDeSenha $hash,
    ) {}

    public function execute(RedefinirSenhaPorLinkInput $input): void
    {
        $usuario = $this->autorizacao->buscar($input->usuarioId);

        if (! $usuario->isAtivo()) {
            throw new RegraDeIdentidadeException('Usuário inativo.');
        }

        PoliticaDeSenha::validar($input->novaSenha);
        if ($this->hash->confere($input->novaSenha, $usuario->getSenhaHash())) {
            throw new RegraDeIdentidadeException('A nova senha deve ser diferente da atual.');
        }

        $usuario->definirSenhaPropria($this->hash->gerar($input->novaSenha));

        $this->usuarios->salvar($usuario);
    }
}
