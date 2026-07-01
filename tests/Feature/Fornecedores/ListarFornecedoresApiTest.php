<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('deve retornar a lista de fornecedores ativos e suas respectivas solucoes', function () {
    // Arrange: Semeia um fornecedor no banco em memória
    $fornecedor = FornecedorEloquentModel::create([
        'id' => Str::uuid()->toString(),
        'nome_fantasia' => 'CrowdStrike',
        'ativo' => true,
    ]);

    $fornecedor->solucoes()->create([
        'id' => Str::uuid()->toString(),
        'nome' => 'Falcon Horizon',
        'ativa' => true,
    ]);

    // Act
    $response = $this->getJson('/api/v1/fornecedores');

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nome_fantasia',
                    'ativo',
                    'solucoes' => [
                        '*' => ['id', 'nome', 'ativa']
                    ]
                ]
            ]
        ]);
});