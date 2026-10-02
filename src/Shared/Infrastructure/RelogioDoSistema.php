<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure;

use DateTimeImmutable;
use Src\Shared\Application\Ports\Relogio;

final class RelogioDoSistema implements Relogio
{
    public function hoje(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface(now()->startOfDay());
    }
}
