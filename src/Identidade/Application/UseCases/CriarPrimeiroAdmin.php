<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\DTOs\CriarPrimeiroAdminInput;
use Src\Identidade\Application\Ports\HashDeSenha;
use Src\Identidade\Domain\Entities\Usuario;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\UsuarioRepositoryInterface;
use Src\Identidade\Domain\ValueObjects\Email;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;
use Src\Shared\Application\Ports\GeradorDeId;

/**
 * Instalação (RN-39): cria o primeiro Admin Geral do Sistema pelo terminal. Só funciona enquanto não houver nenhum Admin Geral do Sistema ativo —
 * depois disso, usuários são cadastrados pelo Admin na tela (RN-35).
 */
final readonly class CriarPrimeiroAdmin
{
    public function __construct(
        private UsuarioRepositoryInterface $usuarios,
        private HashDeSenha $hash,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(CriarPrimeiroAdminInput $input): string
    {
        if ($this->usuarios->contarAdminsGeraisAtivos() > 0) {
            throw new RegraDeIdentidadeException('Já existe um Admin Geral do Sistema ativo: cadastre novos usuários pela tela Usuários.');
        }

        $email = new Email($input->email);
        if ($this->usuarios->emailEmUso($email->valor)) {
            throw new RegraDeIdentidadeException('Já existe um usuário com este e-mail.');
        }

        PoliticaDeSenha::validar($input->senha);

        $admin = Usuario::cadastrar($this->geradorDeId->gerar(), $input->nome, $email, [Papel::ADMIN_GERAL], $this->hash->gerar($input->senha));
        $admin->definirSenhaPropria($this->hash->gerar($input->senha)); // senha escolhida por ele mesmo: sem troca obrigatória

        $this->usuarios->salvar($admin);

        return $admin->getId();
    }
}
