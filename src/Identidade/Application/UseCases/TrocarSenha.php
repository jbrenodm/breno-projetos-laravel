<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\TrocarSenhaInput;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;

/** RN-36/RN-38/RN-40: o próprio usuário troca a senha informando a atual. */
final readonly class TrocarSenha
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
        private HashDeSenha $hash,
    ) {}

    public function execute(TrocarSenhaInput $input): void
    {
        $usuario = $this->autorizacao->buscar($input->usuarioId);

        if (! $this->hash->confere($input->senhaAtual, $usuario->getSenhaHash())) {
            throw new RegraDeIdentidadeException('A senha atual não confere.');
        }

        PoliticaDeSenha::validar($input->novaSenha);
        if ($this->hash->confere($input->novaSenha, $usuario->getSenhaHash())) {
            throw new RegraDeIdentidadeException('A nova senha deve ser diferente da atual.');
        }

        $usuario->definirSenhaPropria($this->hash->gerar($input->novaSenha));

        $this->usuarios->salvar($usuario);
    }
}
