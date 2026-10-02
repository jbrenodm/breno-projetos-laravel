<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Src\Identidade\Domain\Papel;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 * RN-34: todo o sistema exige login. Os testes de API e de telas rodam autenticados como um Admin Geral do Sistema
 * ($this->usuarioLogado). Os testes de autenticação (Feature/Auth) controlam o login por conta própria.
 */
uses()->beforeEach(function () {
    $this->usuarioLogado = User::factory()->comPapel(Papel::ADMIN_GERAL)->create(['name' => 'Admin Logado']);
    Sanctum::actingAs($this->usuarioLogado);
})->in('Feature/Api');

uses()->beforeEach(function () {
    $this->usuarioLogado = User::factory()->comPapel(Papel::ADMIN_GERAL)->create(['name' => 'Admin Logado']);
    $this->actingAs($this->usuarioLogado);
})->in('Feature/Telas');

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
