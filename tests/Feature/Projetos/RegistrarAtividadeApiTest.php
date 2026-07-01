<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Illuminate\Support\Str;

// Usamos a trait RefreshDatabase para garantir que a base de dados de testes
// seja limpa a cada execução, isolando os testes perfeitamente.
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->projetoId = Str::uuid()->toString();
    $this->clienteId = Str::uuid()->toString();
    $this->fornecedorId = Str::uuid()->toString();

    // Cenário de Arranjo (Arrange): Precisamos que um projeto exista na base de dados
    // para podermos anexar atividades a ele através da API.
    $this->projetoModel = ProjetoEloquentModel::create([
        'id' => $this->projetoId,
        'cliente_id' => $this->clienteId,
        'status' => StatusProjeto::NAO_INICIADO->value,
        'codigo_oportunidade' => 'OPP-2026-XYZ',
    ]);

    // Vincula o fornecedor obrigatório na tabela pivot do Eloquent
    $this->projetoModel->fornecedores()->create([
        'fornecedor_id' => $this->fornecedorId,
        'solucao_id' => null,
    ]);
});

it('deve registar uma atividade com sucesso via API quando os responsáveis são informados', function () {
    // Act: Simula uma requisição HTTP POST na rota da API
    $response = $this->postJson("/api/v1/projetos/{$this->projetoId}/atividades", [
        'descricao' => 'Reunião de kickoff técnico com o cliente',
        'status' => 'Em Andamento',
        'data_entrada' => '2026-06-26 10:00:00',
        'deadline' => '2026-06-30 18:00:00',
        'account_manager_id' => Str::uuid()->toString(),
        'pre_vendas_id' => Str::uuid()->toString(),
    ]);

    // Assert: Valida se a API respondeu HTTP 21 Created e a estrutura JSON correta
    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Atividade registrada com sucesso.',
        ]);

    // Valida se o dado foi realmente gravado na tabela do PostgreSQL
    $this->assertDatabaseHas('atividades', [
        'projeto_id' => $this->projetoId,
        'descricao' => 'Reunião de kickoff técnico com o cliente',
        'status' => 'Em Andamento',
    ]);
});

it('deve aplicar o Fallback Temporal e herdar os responsáveis da atividade anterior', function () {
    $accountManagerId = Str::uuid()->toString();
    $preVendasId = Str::uuid()->toString();

    // 1. Inserimos a primeira atividade diretamente via relacionamento para servir de histórico
    $this->projetoModel->atividades()->create([
        'id' => Str::uuid()->toString(),
        'descricao' => 'Atividade 1 (Origem do Histórico)',
        'status' => 'Concluída',
        'data_entrada' => '2026-06-20 08:00:00',
        'deadline' => '2026-06-22 18:00:00',
        'account_manager_id' => $accountManagerId,
        'pre_vendas_id' => $preVendasId,
    ]);

    // 2. Act: Fazemos a requisição para a segunda atividade OMITINDO os responsáveis
    $response = $this->postJson("/api/v1/projetos/{$this->projetoId}/atividades", [
        'descricao' => 'Atividade 2 (Deve herdar os responsáveis da Atividade 1)',
        'status' => 'Não Iniciada',
        'data_entrada' => '2026-06-26 10:00:00',
        'deadline' => '2026-06-30 18:00:00',
        'account_manager_id' => null, // Nulo para forçar o fallback
        'pre_vendas_id' => null,       // Nulo para forçar o fallback
    ]);

    // Assert
    $response->assertStatus(201);

    // Verifica se a nova atividade foi salva herdando corretamente os IDs da atividade anterior
    $this->assertDatabaseHas('atividades', [
        'projeto_id' => $this->projetoId,
        'descricao' => 'Atividade 2 (Deve herdar os responsáveis da Atividade 1)',
        'account_manager_id' => $accountManagerId,
        'pre_vendas_id' => $preVendasId,
    ]);
});

it('deve falhar com HTTP 422 se os dados obrigatórios de validação sintática falharem', function () {
    // Envia dados vazios para disparar o mecanismo de validação da Request do Laravel
    $response = $this->postJson("/api/v1/projetos/{$this->projetoId}/atividades", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['descricao', 'status', 'data_entrada', 'deadline']);
});