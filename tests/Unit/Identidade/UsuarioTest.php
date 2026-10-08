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

it('RN-35/RN-36: nasce ativo, com senha temporária; usuário comum não tem papel', function () {
    $u = Usuario::cadastrar(uuid(), ' <b>Ana</b> ', new Email('ana@x.com'), [], 'hash');

    expect($u->getNome())->toBe('Ana')
        ->and($u->isAtivo())->toBeTrue()
        ->and($u->deveTrocarSenha())->toBeTrue()
        ->and($u->getPapeis())->toBe([])
        ->and($u->ehAdminGeralAtivo())->toBeFalse();

    $admin = Usuario::cadastrar(uuid(), 'Bia', new Email('bia@x.com'), [Papel::ADMIN_GERAL, Papel::ADMIN_GERAL], 'hash');
    expect($admin->getPapeis())->toBe([Papel::ADMIN_GERAL])->and($admin->ehAdminGeralAtivo())->toBeTrue();

    $u->definirSenhaPropria('novo-hash');
    expect($u->deveTrocarSenha())->toBeFalse();
    $u->definirSenhaTemporaria('temp');
    expect($u->deveTrocarSenha())->toBeTrue();
});

it('RN-25/RN-42: usuário não pode ter papel de AM ou PV (são Responsáveis)', function (Papel $papel) {
    Usuario::cadastrar(uuid(), 'Ana', new Email('ana@x.com'), [Papel::ADMIN_GERAL, $papel], 'hash');
})->with([Papel::ACCOUNT_MANAGER, Papel::PRE_VENDAS])
    ->throws(RegraDeIdentidadeException::class, 'Usuário só pode ter o papel de Admin Geral do Sistema. AM e PV são cadastrados em Responsáveis.');

it('Admin Geral do Sistema inativo não conta como Admin ativo', function () {
    $u = Usuario::cadastrar(uuid(), 'Admin', new Email('a@x.com'), [Papel::ADMIN_GERAL], 'hash');
    expect($u->ehAdminGeralAtivo())->toBeTrue();
    $u->inativar();
    expect($u->ehAdminGeralAtivo())->toBeFalse();
});
