<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\ValueObjects;

use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;

/** RN-22: armazenado apenas com dígitos; valida os dígitos verificadores. */
final readonly class Cnpj
{
    public string $valor;

    public function __construct(string $valor)
    {
        $digitos = preg_replace('/\D/', '', $valor) ?? '';

        if (! self::ehValido($digitos)) {
            throw new RegraDeParceiroException('CNPJ inválido.');
        }

        $this->valor = $digitos;
    }

    public static function opcional(?string $valor): ?self
    {
        return ($valor === null || trim($valor) === '') ? null : new self($valor);
    }

    public function formatado(): string
    {
        return vsprintf('%s.%s.%s/%s-%s', [
            substr($this->valor, 0, 2), substr($this->valor, 2, 3), substr($this->valor, 5, 3),
            substr($this->valor, 8, 4), substr($this->valor, 12, 2),
        ]);
    }

    private static function ehValido(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        foreach ([12, 13] as $posicao) {
            $pesos = $posicao === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $pesos[$i];
            }
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
