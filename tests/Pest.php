<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** Data sem horário para testes de domínio. */
function dia(string $data): DateTimeImmutable
{
    return new DateTimeImmutable($data.' 00:00:00');
}

/** UUID v4 aleatório sem depender do Laravel (testes unitários). */
function uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}
