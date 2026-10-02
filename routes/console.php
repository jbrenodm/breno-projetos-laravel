<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Src\Identidade\Application\DTOs\CriarPrimeiroAdminInput;
use Src\Identidade\Application\UseCases\CriarPrimeiroAdmin;
use Src\Shared\Domain\RegraDeNegocioException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Instalação: cria o primeiro Admin Geral do Sistema (só funciona enquanto não houver nenhum Admin Geral do Sistema ativo).
Artisan::command('usuarios:criar-admin {email} {nome}', function (CriarPrimeiroAdmin $useCase) {
    $senha = (string) $this->secret('Senha (mínimo 8 caracteres, com letras e números)');
    if ($senha !== (string) $this->secret('Confirme a senha')) {
        $this->error('As senhas não conferem.');

        return 1;
    }

    try {
        $useCase->execute(new CriarPrimeiroAdminInput($this->argument('nome'), $this->argument('email'), $senha));
    } catch (RegraDeNegocioException $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info('Admin Geral do Sistema criado. Entre no sistema com este e-mail.');

    return 0;
})->purpose('Cria o primeiro Admin Geral do Sistema (instalação)');
