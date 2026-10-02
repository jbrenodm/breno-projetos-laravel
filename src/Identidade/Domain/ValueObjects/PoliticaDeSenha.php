<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\ValueObjects;

use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;

/** RN-38: mínimo de 8 caracteres, com letras e números. */
final class PoliticaDeSenha
{
    public const TAMANHO_MINIMO = 8;

    public static function validar(string $senha): void
    {
        if (mb_strlen($senha) < self::TAMANHO_MINIMO || mb_strlen($senha) > 255
            || preg_match('/\pL/u', $senha) !== 1 || preg_match('/\d/', $senha) !== 1) {
            throw new RegraDeIdentidadeException('A senha deve ter no mínimo 8 caracteres, com letras e números.');
        }
    }
}
