<?php

declare(strict_types=1);

namespace Src\Identidade\Application\Ports;

/** REQUISITOS.md §8: criptografia/hash via interface, implementada na Infraestrutura. */
interface HashDeSenha
{
    public function gerar(string $senha): string;

    public function confere(string $senha, string $hash): bool;
}
