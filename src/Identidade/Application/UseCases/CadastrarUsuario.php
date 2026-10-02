<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\CadastrarUsuarioInput;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Domain\Entities\Usuario;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\Email;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;
use Src\Shared\Application\Ports\GeradorDeId;

/** RN-35/RN-36 (só Admin Geral do Sistema) */
final readonly class CadastrarUsuario
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
        private HashDeSenha $hash,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CadastrarUsuarioInput $input): string
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);

        $email = new Email($input->email);
        if ($this->usuarios->emailEmUso($email->valor)) {
            throw new RegraDeIdentidadeException('Já existe um usuário com este e-mail.');
        }

        PoliticaDeSenha::validar($input->senhaTemporaria);

        $usuario = Usuario::cadastrar(
            $this->geradorDeId->gerar(),
            $input->nome,
            $email,
            AutorizacaoDeUsuarios::papeis($input->papeis),
            $this->hash->gerar($input->senhaTemporaria),
        );

        $this->usuarios->salvar($usuario);

        return $usuario->getId();
    }
}
