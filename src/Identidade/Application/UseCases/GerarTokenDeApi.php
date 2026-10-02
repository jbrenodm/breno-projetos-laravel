<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\TokenDeApiInput;
use Src\Identidade\Application\Ports\AcessosDoUsuario;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;

/** RN-34/RN-40: devolve o token em texto puro — exibido uma única vez. */
final readonly class GerarTokenDeApi
{
    public function __construct(
        private AutorizacaoDeUsuarios $autorizacao,
        private AcessosDoUsuario $acessos,
    ) {}

    public function execute(TokenDeApiInput $input): string
    {
        $usuario = $this->autorizacao->buscar($input->usuarioId);
        $nome = trim(strip_tags($input->valor));

        if (! $usuario->isAtivo()) {
            throw new RegraDeIdentidadeException('Usuário inativo.');
        }

        if ($nome === '' || mb_strlen($nome) > 100) {
            throw new RegraDeIdentidadeException('Dê um nome ao token (máximo 100 caracteres).');
        }

        return $this->acessos->emitirToken($usuario->getId(), $nome);
    }
}
