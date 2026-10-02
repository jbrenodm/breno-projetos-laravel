<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Identidade\Domain\Papel;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Shared\Application\Ports\Relogio;

beforeEach(function () {
    $this->clienteId = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Cliente S.A.'));
    $this->fornecedorId = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Fornecedor Ltda', 'Forn', null, ['Solução X']));
    $this->solucaoId = app(ParceirosQuery::class)->listarFornecedores()[0]['solucoes'][0]['id'];
    $this->am = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create();
    $this->pv = User::factory()->comPapel(Papel::PRE_VENDAS)->create();

    app()->instance(Relogio::class, new class implements Relogio
    {
        public function hoje(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-10-02');
        }
    });
});

function criarProjetoViaApi($test, array $extra = []): string
{
    return $test->postJson('/api/v1/projetos', $extra + [
        'cliente_id' => $test->clienteId,
        'codigo_oportunidade' => 'OPP-2026-0001',
        'fornecedores' => [['fornecedor_id' => $test->fornecedorId, 'solucao_id' => $test->solucaoId]],
    ])->assertCreated()->json('id');
}

function atividadePayload($test, array $extra = []): array
{
    return $extra + [
        'descricao' => 'Reunião de kickoff',
        'tipo' => 'Comercial',
        'status' => 'Não Iniciada',
        'data_entrada' => '2026-10-01',
        'data_limite' => '2026-10-15',
        'account_manager_id' => $test->am->id,
        'pre_vendas_id' => $test->pv->id,
    ];
}

it('registra projeto com fornecedor e solução (RN-01..07)', function () {
    $id = criarProjetoViaApi($this);

    $this->getJson("/api/v1/projetos/{$id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'Não Iniciado')
        ->assertJsonPath('data.codigo_oportunidade', 'OPP-2026-0001')
        ->assertJsonPath('data.fornecedores.0.solucao', 'Solução X');
});

it('registra projeto só com fornecedor, sem solução (RN-04)', function () {
    $id = criarProjetoViaApi($this, ['fornecedores' => [['fornecedor_id' => $this->fornecedorId]]]);

    $this->assertDatabaseHas('projeto_fornecedores', ['projeto_id' => $id, 'solucao_id' => null]);
});

it('rejeita solução de outro fornecedor (RN-05)', function () {
    $outro = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Outro Ltda'));

    $this->postJson('/api/v1/projetos', [
        'cliente_id' => $this->clienteId,
        'fornecedores' => [['fornecedor_id' => $outro, 'solucao_id' => $this->solucaoId]],
    ])->assertUnprocessable()->assertJsonPath('error', 'A solução informada não pertence ao fornecedor ou está inativa.');
});

it('rejeita cliente inexistente (RN-06)', function () {
    $this->postJson('/api/v1/projetos', [
        'cliente_id' => (string) Str::uuid(),
        'fornecedores' => [['fornecedor_id' => $this->fornecedorId]],
    ])->assertUnprocessable();
});

it('exige pelo menos um fornecedor (validação de borda)', function () {
    $this->postJson('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => []])
        ->assertUnprocessable()->assertJsonValidationErrors('fornecedores');
});

it('ciclo completo: atividades, herança de AM/PV e status do projeto', function () {
    $id = criarProjetoViaApi($this);

    // 1ª atividade sem AM/PV → erro de regra
    $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this, ['account_manager_id' => null, 'pre_vendas_id' => null]))
        ->assertUnprocessable();

    $a1 = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))->assertCreated()->json('id');

    // 2ª sem AM/PV → herda (RN-14)
    $a2 = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this, [
        'descricao' => 'Histórico', 'status' => 'Concluída', 'tipo' => 'Mapeamento',
        'account_manager_id' => null, 'pre_vendas_id' => null, 'data_termino' => '2026-10-01',
    ]))->assertCreated()->json('id');

    $this->assertDatabaseHas('atividades', ['id' => $a2, 'account_manager_id' => $this->am->id, 'sequencia' => 2]);
    $this->assertDatabaseHas('projetos', ['id' => $id, 'status' => 'Em Andamento']);

    // inicia e conclui a 1ª → projeto Concluído (RN-09)
    $this->patchJson("/api/v1/projetos/{$id}/atividades/{$a1}/status", ['status' => 'Em Andamento'])->assertOk();
    $this->patchJson("/api/v1/projetos/{$id}/atividades/{$a1}/status", ['status' => 'Concluída', 'data' => '2026-10-02'])->assertOk();

    $atividade = DB::table('atividades')->where('id', $a1)->first();
    expect($atividade->data_inicio)->toStartWith('2026-10-02')
        ->and($atividade->data_termino)->toStartWith('2026-10-02');
    $this->assertDatabaseHas('projetos', ['id' => $id, 'status' => 'Concluído']);

    // transição inválida (RN-16)
    $this->patchJson("/api/v1/projetos/{$id}/atividades/{$a1}/status", ['status' => 'Em Andamento'])->assertUnprocessable();
});

it('rejeita AM que não tem o papel de Account Manager (RN-25)', function () {
    $id = criarProjetoViaApi($this);

    $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this, ['account_manager_id' => $this->pv->id]))
        ->assertUnprocessable();
});

it('cancela projeto e bloqueia novas atividades (RN-11)', function () {
    $id = criarProjetoViaApi($this);
    $this->postJson("/api/v1/projetos/{$id}/cancelar")->assertOk();

    $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))
        ->assertUnprocessable()->assertJsonPath('error', 'Projeto cancelado não pode ser alterado.');
});

it('retorna 404 para projeto inexistente', function () {
    $this->getJson('/api/v1/projetos/'.Str::uuid())->assertNotFound();
    $this->getJson('/api/v1/projetos/nao-e-uuid')->assertNotFound();
});

it('ignora campos extras (sem mass assignment)', function () {
    $id = criarProjetoViaApi($this, ['status' => 'Concluído', 'id' => (string) Str::uuid()]);

    $this->assertDatabaseHas('projetos', ['id' => $id, 'status' => 'Não Iniciado']);
});
