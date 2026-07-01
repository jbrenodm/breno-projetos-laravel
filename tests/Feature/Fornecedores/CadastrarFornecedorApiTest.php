<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deve cadastrar um fornecedor com seu catalogo inicial de solucoes com sucesso', function () {
    $response = $this->postJson('/api/v1/fornecedores', [
        'nome_fantasia' => 'Veeam Software',
        'solucoes' => [
            'Veeam Backup & Replication',
            'Veeam ONE'
        ]
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'message', 'id']);

    $fornecedorId = $response->json('id');

    // Verifica a persistência da raiz do Agregado
    $this->assertDatabaseHas('fornecedores', [
        'id' => $fornecedorId,
        'nome_fantasia' => 'Veeam Software',
        'ativo' => true
    ]);

    // Verifica a persistência das soluções vinculadas
    $this->assertDatabaseHas('solucoes', [
        'fornecedor_id' => $fornecedorId,
        'nome' => 'Veeam Backup & Replication'
    ]);

    $this->assertDatabaseHas('solucoes', [
        'fornecedor_id' => $fornecedorId,
        'nome' => 'Veeam ONE'
    ]);
});

it('deve rejeitar o cadastro se houver solucao com nome duplicado para o mesmo fornecedor', function () {
    $response = $this->postJson('/api/v1/fornecedores', [
        'nome_fantasia' => 'VMware corp',
        'solucoes' => [
            'vSphere Pro',
            'vSphere Pro' // Forçando duplicidade para quebrar a invariante de domínio
        ]
    ]);

    // O Exception Handler Global precisa de interceptar e devolver 422
    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error' => "Este fornecedor já possui uma solução cadastrada com o nome 'vSphere Pro'."
        ]);
});