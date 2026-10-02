<?php

declare(strict_types=1);

use Src\Identidade\Domain\Entities\Usuario;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\ValueObjects\Email;
use Src\Identidade\Domain\ValueObjects\PoliticaDeSenha;

it('RN-35: e-mail é normalizado para minúsculas e validado', function () {
    expect((new Email('  Ana.AM@Breno.Local '))->valor)->toBe('ana.am@breno.local');

    new Email('nao-e-email');
})->throws(RegraDeIdentidadeException::class, 'E-mail inválido.');

it('RN-38: senha precisa de 8+ caracteres com letras e números', function (string $senha) {
    PoliticaDeSenha::validar($senha);
})->with(['curta1', 'somenteletras', '12345678', ''])->throws(RegraDeIdentidadeException::class);

it('RN-38: aceita senha válida (inclusive com acentos)', function () {
    PoliticaDeSenha::validar('Senha123');
    PoliticaDeSenha::validar('ação2026x');
    expect(true)->toBeTrue();
});

it('RN-35/RN-36: nasce ativo, com senha temporária e papéis sem repetição', function () {
    $u = Usuario::cadastrar(uuid(), ' <b>Ana</b> ', new Email('ana@x.com'), [Papel::ACCOUNT_MANAGER, Papel::ACCOUNT_MANAGER], 'hash');

    expect($u->getNome())->toBe('Ana')
        ->and($u->isAtivo())->toBeTrue()
        ->and($u->deveTrocarSenha())->toBeTrue()
        ->and($u->getPapeis())->toBe([Papel::ACCOUNT_MANAGER])
        ->and($u->ehAdminGeralAtivo())->toBeFalse();

    $u->definirSenhaPropria('novo-hash');
    expect($u->deveTrocarSenha())->toBeFalse();
    $u->definirSenhaTemporaria('temp');
    expect($u->deveTrocarSenha())->toBeTrue();
});

it('RN-35: exige pelo menos um papel', function () {
    Usuario::cadastrar(uuid(), 'Ana', new Email('ana@x.com'), [], 'hash');
})->throws(RegraDeIdentidadeException::class, 'O usuário deve ter pelo menos um papel.');

it('Admin Geral do Sistema inativo não conta como Admin ativo', function () {
    $u = Usuario::cadastrar(uuid(), 'Admin', new Email('a@x.com'), [Papel::ADMIN_GERAL], 'hash');
    expect($u->ehAdminGeralAtivo())->toBeTrue();
    $u->inativar();
    expect($u->ehAdminGeralAtivo())->toBeFalse();
});
