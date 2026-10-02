<?php

declare(strict_types=1);

namespace Src\Shared\Application;

use RuntimeException;

/**
 * Lançada quando um agregado não existe (ou o usuário não tem acesso a ele — evita vazar existência de IDs).
 */
final class RecursoNaoEncontradoException extends RuntimeException
{
    public static function para(string $recurso, string $id): self
    {
        return new self("{$recurso} {$id} não encontrado.");
    }
}
