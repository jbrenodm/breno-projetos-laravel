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
