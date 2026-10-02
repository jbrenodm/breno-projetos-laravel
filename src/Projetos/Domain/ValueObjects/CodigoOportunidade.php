<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;

/** RN-02: código opcional vindo do comercial/CRM. */
final readonly class CodigoOportunidade
{
    private const TAMANHO_MAXIMO = 50;

    private string $valor;

    public function __construct(string $valor)
    {
        $limpo = trim($valor);

        if ($limpo === '') {
            throw new RegraDeProjetoException('O código da oportunidade não pode ser vazio.');
        }

        if (mb_strlen($limpo) > self::TAMANHO_MAXIMO) {
            throw new RegraDeProjetoException('O código da oportunidade deve ter no máximo 50 caracteres.');
        }

        if (preg_match('/^[\p{L}\p{N}\-_.\/ ]+$/u', $limpo) !== 1) {
            throw new RegraDeProjetoException('O código da oportunidade contém caracteres inválidos.');
        }

        $this->valor = $limpo;
    }

    public static function opcional(?string $valor): ?self
    {
        return ($valor === null || trim($valor) === '') ? null : new self($valor);
    }

    public function toString(): string
    {
        return $this->valor;
    }
}
