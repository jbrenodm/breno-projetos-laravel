<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('deve retornar os detalhes completos do projeto e suas atividades', function () {
    $projetoId = Str::uuid()->toString();

    // 1. Cria o cenário diretamente no banco de dados de teste
    $projeto = ProjetoEloquentModel::create([
        'id' => $projetoId,
        'cliente_id' => Str::uuid()->toString(),
        'status' => StatusProjeto::EM_ANDAMENTO->value,
        'codigo_oportunidade' => 'OPP-GET-2026',
    ]);

    $projeto->fornecedores()->create([
        'fornecedor_id' => Str::uuid()->toString(),
        'solucao_id' => Str::uuid()->toString(),
    ]);

    $projeto->atividades()->create([
        'id' => Str::uuid()->toString(),
        'descricao' => 'Análise de Infraestrutura Concluída',
        'status' => 'Concluída',
        'data_entrada' => '2026-06-26 09:00:00',
        'deadline' => '2026-06-26 18:00:00',
        'account_manager_id' => Str::uuid()->toString(),
        'pre_vendas_id' => Str::uuid()->toString(),
    ]);

    // 2. Executa a consulta via HTTP GET
    $response = $this->getJson("/api/v1/projetos/{$projetoId}");

    // 3. Validações estruturais do JSON retornado pelo API Resource
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'cliente_id',
                'codigo_oportunidade',
                'status',
                'fornecedores' => [
                    '*' => ['fornecedor_id', 'solucao_id']
                ],
                'atividades' => [
                    '*' => [
                        'id',
                        'descricao',
                        'status',
                        'responsaveis' => ['account_manager_id', 'pre_vendas_id'],
                        'prazos' => ['data_entrada', 'deadline', 'data_termino'],
                        'criado_em'
                    ]
                ],
                'atualizado_em'
            ]
        ]);
});

it('deve retornar 404 se o projeto consultado nao existir', function () {
    $idInexistente = Str::uuid()->toString();

    $response = $this->getJson("/api/v1/projetos/{$idInexistente}");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error' => "Projeto com ID {$idInexistente} não foi encontrado."
        ]);
});