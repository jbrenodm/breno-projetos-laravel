<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('deve concluir uma atividade com sucesso mudando o status e registrando a data de termino', function () {
    $projetoId = Str::uuid()->toString();
    $atividadeId = Str::uuid()->toString();

    // Arrange: Cria projeto e atividade no banco de dados de teste
    $projeto = ProjetoEloquentModel::create([
        'id' => $projetoId,
        'cliente_id' => Str::uuid()->toString(),
        'status' => StatusProjeto::EM_ANDAMENTO->value,
    ]);

    $projeto->fornecedores()->create([
        'fornecedor_id' => Str::uuid()->toString(),
        'solucao_id' => null,
    ]);

    $projeto->atividades()->create([
        'id' => $atividadeId,
        'descricao' => 'Desenho da Topologia de Rede',
        'status' => 'Em Andamento',
        'data_entrada' => '2026-06-20 08:00:00',
        'deadline' => '2026-06-25 18:00:00',
        'account_manager_id' => Str::uuid()->toString(),
        'pre_vendas_id' => Str::uuid()->toString(),
    ]);

    // Act: Dispara o PATCH para concluir a atividade
    $response = $this->patchJson("/api/v1/projetos/{$projetoId}/atividades/{$atividadeId}/concluir", [
        'data_termino' => '2026-06-24 14:30:00',
    ]);

    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Atividade concluída com sucesso.',
        ]);

    // Verifica se os dados mudaram de fato na tabela do banco
    $this->assertDatabaseHas('atividades', [
        'id' => $atividadeId,
        'status' => 'Concluída',
        'data_termino' => '2026-06-24 14:30:00',
    ]);
});