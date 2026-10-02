<?php

declare(strict_types=1);

namespace Src\Shared\Application;

use RuntimeException;

/** RN-27: o usuário autenticado não tem permissão para executar o caso de uso (HTTP 403). */
final class AcessoNegadoException extends RuntimeException
{
    public static function somenteAdminGeral(): self
    {
        return new self('Somente o Admin Geral do Sistema pode realizar esta operação.');
    }
}
