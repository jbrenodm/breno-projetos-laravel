<?php

declare(strict_types=1);

it('cadastra cliente só com razão social (RN-21)', function () {
    $this->postJson('/api/v1/clientes', ['razao_social' => 'Cliente Sem CNPJ'])->assertCreated();
    $this->postJson('/api/v1/clientes', ['razao_social' => 'Outro Sem CNPJ'])->assertCreated();

    expect($this->getJson('/api/v1/clientes')->json('data'))->toHaveCount(2);
});

it('CNPJ é único quando informado (RN-22)', function () {
    $this->postJson('/api/v1/clientes', ['razao_social' => 'A', 'cnpj' => '11.222.333/0001-81'])->assertCreated();
    $this->postJson('/api/v1/clientes', ['razao_social' => 'B', 'cnpj' => '11222333000181'])
        ->assertUnprocessable()->assertJsonPath('error', 'Já existe um cliente cadastrado com este CNPJ.');
});

it('rejeita CNPJ inválido', function () {
    $this->postJson('/api/v1/fornecedores', ['razao_social' => 'F', 'cnpj' => '12345678000100'])
        ->assertUnprocessable()->assertJsonPath('error', 'CNPJ inválido.');
});

it('cadastra fornecedor com soluções (RN-23)', function () {
    $this->postJson('/api/v1/fornecedores', ['razao_social' => 'Forn Ltda', 'solucoes' => ['A', 'B']])->assertCreated();

    expect($this->getJson('/api/v1/fornecedores')->json('data.0.solucoes'))->toHaveCount(2);
});

it('edita todos os dados do cliente, inclusive CNPJ único (RN-30)', function () {
    $a = $this->postJson('/api/v1/clientes', ['razao_social' => 'A', 'cnpj' => '11.222.333/0001-81'])->json('id');
    $b = $this->postJson('/api/v1/clientes', ['razao_social' => 'B'])->json('id');

    $this->putJson("/api/v1/clientes/{$b}", ['razao_social' => 'B Editado S.A.', 'nome_fantasia' => 'B Novo', 'cnpj' => '11.444.777/0001-61'])->assertOk();
    $this->assertDatabaseHas('clientes', ['id' => $b, 'razao_social' => 'B Editado S.A.', 'nome_fantasia' => 'B Novo', 'cnpj' => '11444777000161']);

    $this->putJson("/api/v1/clientes/{$b}", ['razao_social' => 'B', 'cnpj' => '11222333000181'])
        ->assertUnprocessable()->assertJsonPath('error', 'Já existe um cliente cadastrado com este CNPJ.');
    $this->putJson("/api/v1/clientes/{$a}", ['razao_social' => 'A', 'cnpj' => '11222333000181'])->assertOk(); // o próprio CNPJ
    $this->putJson("/api/v1/clientes/{$a}", ['razao_social' => 'A'])->assertOk();                         // remove o CNPJ
    $this->assertDatabaseHas('clientes', ['id' => $a, 'cnpj' => null]);

    $this->putJson("/api/v1/clientes/{$a}", ['razao_social' => ''])->assertUnprocessable();
    $this->putJson('/api/v1/clientes/'.Str::uuid(), ['razao_social' => 'X'])->assertNotFound();
    $this->putJson('/api/v1/clientes/nao-e-uuid', ['razao_social' => 'X'])->assertNotFound();
});

it('edita fornecedor e suas soluções (RN-30/RN-32)', function () {
    $f = $this->postJson('/api/v1/fornecedores', ['razao_social' => 'Sophos Ltda', 'solucoes' => ['XGS', 'Intercept X']])->json('id');
    $solucoes = collect($this->getJson('/api/v1/fornecedores')->json('data.0.solucoes'))->pluck('id', 'nome');

    $this->putJson("/api/v1/fornecedores/{$f}", ['razao_social' => 'Sophos Brasil Ltda', 'nome_fantasia' => 'Sophos', 'cnpj' => '11.444.777/0001-61'])->assertOk();
    $this->assertDatabaseHas('fornecedores', ['id' => $f, 'razao_social' => 'Sophos Brasil Ltda', 'cnpj' => '11444777000161']);

    $this->putJson("/api/v1/fornecedores/{$f}/solucoes/{$solucoes['XGS']}", ['nome' => 'XGS Firewall', 'descricao' => 'NGFW'])->assertOk();
    $this->assertDatabaseHas('solucoes', ['id' => $solucoes['XGS'], 'nome' => 'XGS Firewall', 'descricao' => 'NGFW']);

    $this->putJson("/api/v1/fornecedores/{$f}/solucoes/{$solucoes['XGS']}", ['nome' => 'Intercept X'])
        ->assertUnprocessable()->assertJsonPath('error', "Este fornecedor já possui a solução 'Intercept X'.");
});

it('inativa e reativa cliente, fornecedor e solução sem alterar projetos existentes (RN-31/RN-24)', function () {
    $cliente = $this->postJson('/api/v1/clientes', ['razao_social' => 'Cliente S.A.'])->json('id');
    $fornecedor = $this->postJson('/api/v1/fornecedores', ['razao_social' => 'Forn Ltda', 'solucoes' => ['EDR']])->json('id');
    $solucao = $this->getJson('/api/v1/fornecedores')->json('data.0.solucoes.0.id');
    $projeto = $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor, 'solucao_id' => $solucao]]])->json('id');

    $this->patchJson("/api/v1/clientes/{$cliente}/situacao", ['ativo' => false])->assertOk();
    $this->patchJson("/api/v1/fornecedores/{$fornecedor}/solucoes/{$solucao}/situacao", ['ativo' => false])->assertOk();
    $this->patchJson("/api/v1/fornecedores/{$fornecedor}/situacao", ['ativo' => false])->assertOk();

    $this->assertDatabaseHas('clientes', ['id' => $cliente, 'ativo' => false]);
    $this->assertDatabaseHas('fornecedores', ['id' => $fornecedor, 'ativo' => false]);
    $this->assertDatabaseHas('solucoes', ['id' => $solucao, 'ativo' => false]);

    // RN-24: o projeto existente continua igual e visível; novos projetos não aceitam os inativos (RN-06)
    $this->getJson("/api/v1/projetos/{$projeto}")->assertOk()
        ->assertJsonPath('data.cliente_id', $cliente)
        ->assertJsonPath('data.fornecedores.0.solucao_id', $solucao);
    $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor]]])->assertUnprocessable();

    $this->patchJson("/api/v1/clientes/{$cliente}/situacao", ['ativo' => true])->assertOk();
    $this->patchJson("/api/v1/fornecedores/{$fornecedor}/situacao", ['ativo' => true])->assertOk();
    $this->patchJson("/api/v1/fornecedores/{$fornecedor}/solucoes/{$solucao}/situacao", ['ativo' => true])->assertOk();
    $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor, 'solucao_id' => $solucao]]])->assertCreated();

    $this->patchJson("/api/v1/clientes/{$cliente}/situacao", [])->assertUnprocessable();
});
