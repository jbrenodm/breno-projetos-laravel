<?php

declare(strict_types=1);

namespace Src\Shared\Domain;

/**
 * Validação de formato UUID sem depender de bibliotecas externas.
 */
final class Uuid
{
    private const PADRAO = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function ehValido(string $valor): bool
    {
        return preg_match(self::PADRAO, $valor) === 1;
    }
}
