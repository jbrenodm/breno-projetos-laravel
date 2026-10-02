<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\EditarUsuarioInput;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\Email;

/** RN-35/RN-39 (só Admin Geral do Sistema) */
final readonly class EditarUsuario
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private AutorizacaoDeUsuarios $autorizacao,
    ) {}

    public function execute(EditarUsuarioInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);
        $usuario = $this->autorizacao->buscar($input->usuarioId);

        $email = new Email($input->email);
        if ($this->usuarios->emailEmUso($email->valor, ignorarId: $usuario->getId())) {
            throw new RegraDeIdentidadeException('Já existe um usuário com este e-mail.');
        }

        $papeis = AutorizacaoDeUsuarios::papeis($input->papeis);
        if (! in_array(Papel::ADMIN_GERAL, $papeis, true)) {
            $this->autorizacao->garantirQueRestaOutroAdmin($usuario);
        }

        $usuario->editar($input->nome, $email, $papeis);

        $this->usuarios->salvar($usuario);
    }
}
