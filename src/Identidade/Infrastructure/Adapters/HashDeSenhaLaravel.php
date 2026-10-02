<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Adapters;

use Illuminate\Support\Facades\Hash;
use Src\Identidade\Application\Ports\HashDeSenha;

final class HashDeSenhaLaravel implements HashDeSenha
{
    public function gerar(string $senha): string
    {
        return Hash::make($senha);
    }

    public function confere(string $senha, string $hash): bool
    {
        return Hash::check($senha, $hash);
    }
}
