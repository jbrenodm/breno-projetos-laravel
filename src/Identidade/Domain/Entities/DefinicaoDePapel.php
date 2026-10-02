<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\Entities;

use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;

/** RN-41: nome exibido e sigla de um papel. O papel em si (identificador interno) não muda. */
final class DefinicaoDePapel
{
    private string $nome;

    private ?string $sigla;

    public function __construct(public readonly Papel $papel, string $nome, ?string $sigla)
    {
        $this->renomear($nome, $sigla);
    }

    public function renomear(string $nome, ?string $sigla): void
    {
        $nome = trim(strip_tags($nome));
        if ($nome === '' || mb_strlen($nome) > 60) {
            throw new RegraDeIdentidadeException('O nome do papel é obrigatório (máximo 60 caracteres).');
        }

        $sigla = $sigla === null ? null : trim(strip_tags($sigla));
        if ($sigla !== null && mb_strlen($sigla) > 10) {
            throw new RegraDeIdentidadeException('A sigla deve ter no máximo 10 caracteres.');
        }

        $this->nome = $nome;
        $this->sigla = $sigla === '' ? null : $sigla;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getSigla(): ?string
    {
        return $this->sigla;
    }
}
