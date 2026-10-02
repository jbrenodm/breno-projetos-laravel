<?php

declare(strict_types=1);

namespace Src\Identidade\Domain\Repositories;

use Src\Identidade\Domain\Entities\DefinicaoDePapel;
use Src\Identidade\Domain\Papel;

interface DefinicaoDePapelRepositoryInterface
{
    public function buscar(Papel $papel): DefinicaoDePapel;

    /** RN-41: sem diferenciar maiúsculas, ignorando o próprio papel. */
    public function nomeEmUso(string $nome, Papel $ignorar): bool;

    public function siglaEmUso(string $sigla, Papel $ignorar): bool;

    public function salvar(DefinicaoDePapel $definicao): void;
}
