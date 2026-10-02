<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure;

use Illuminate\Support\Str;
use Src\Shared\Application\Ports\GeradorDeId;

final class GeradorDeIdUuid implements GeradorDeId
{
    public function gerar(): string
    {
        return (string) Str::uuid();
    }
}
