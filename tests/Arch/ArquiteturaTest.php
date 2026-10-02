<?php

declare(strict_types=1);

/*
 * Guardiões da Clean Architecture (REQUISITOS.md §8).
 * Se um destes testes falhar, alguém colocou framework onde não devia.
 */

$camadasPuras = [
    'Src\Shared\Domain', 'Src\Shared\Application',
    'Src\Projetos\Domain', 'Src\Projetos\Application',
    'Src\Parceiros\Domain', 'Src\Parceiros\Application',
    'Src\Identidade\Domain', 'Src\Identidade\Application',
];

foreach ($camadasPuras as $camada) {
    foreach (['Illuminate', 'Livewire\Component', 'App', 'Carbon'] as $proibido) {
        arch("{$camada} não depende de {$proibido}")
            ->expect($camada)
            ->not->toUse($proibido);
    }

    arch("{$camada} não usa helpers globais do Laravel")
        ->expect($camada)
        ->not->toUse(['collect', 'now', 'app', 'config', 'str', 'request', 'auth']);
}

foreach (['Src\Projetos\Domain', 'Src\Projetos\Application'] as $camada) {
    arch("{$camada} não conhece a Infraestrutura nem outros contextos")
        ->expect($camada)
        ->not->toUse('Src\Projetos\Infrastructure')
        ->not->toUse('Src\Parceiros')
        ->not->toUse('Src\Identidade');
}

arch('Componentes Livewire não acessam banco/Infraestrutura diretamente')
    ->expect('App\Livewire')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->not->toUse('Src\Projetos\Infrastructure')
    ->not->toUse('Src\Parceiros\Infrastructure')
    ->not->toUse('Src\Identidade\Infrastructure');

arch('Sem código de debug esquecido')
    ->expect(['App', 'Src'])
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);
