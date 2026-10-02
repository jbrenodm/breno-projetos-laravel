<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\ValueObjects;

use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;

/** RN-35: e-mail único sem diferenciar maiúsculas — guardado sempre em minúsculas. */
final readonly class Email
{
    public string $valor;

    public function __construct(string $valor)
    {
        $email = mb_strtolower(trim($valor));

        if (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RegraDeIdentidadeException('E-mail inválido.');
        }

        $this->valor = $email;
    }
}
