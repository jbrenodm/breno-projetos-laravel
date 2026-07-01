<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('deve registrar um novo projeto com seus fornecedores com sucesso', function () {
    $clienteId = Str::uuid()->toString();
    $fornecedorId = Str::uuid()->toString();

    $response = $this->postJson('/api/v1/projetos', [
        'cliente_id' => $clienteId,
        'codigo_oportunidade' => 'OPP-2026-009',
        'fornecedores' => [
            [
                'fornecedor_id' => $fornecedorId,
                'solucao_id' => null
            ]
        ]
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'message', 'id']);

    $projetoId = $response->json('id');

    // Valida se o registro foi criado na tabela mestre
    $this->assertDatabaseHas('projetos', [
        'id' => $projetoId,
        'cliente_id' => $clienteId,
        'status' => 'Não Iniciado',
        'codigo_oportunidade' => 'OPP-2026-009',
    ]);

    // Valida se a relação pivot foi criada corretamente
    $this->assertDatabaseHas('projeto_fornecedores', [
        'projeto_id' => $projetoId,
        'fornecedor_id' => $fornecedorId,
        'solucao_id' => null
    ]);
});

it('deve rejeitar a criacao de projeto sem fornecedores', function () {
    $response = $this->postJson('/api/v1/projetos', [
        'cliente_id' => Str::uuid()->toString(),
        'fornecedores' => [] // Inválido pela validação de borda
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fornecedores']);
});