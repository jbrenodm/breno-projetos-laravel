<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Responsaveis\Domain\Funcao;
use Src\Shared\Application\Ports\Relogio;

beforeEach(function () {
    $this->clienteId = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Cliente S.A.'));
    $this->fornecedorId = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Fornecedor Ltda', 'Forn', null, ['Solução X']));
    $this->solucaoId = app(ParceirosQuery::class)->listarFornecedores()[0]['solucoes'][0]['id'];
    $this->am = responsavel('AM', Funcao::ACCOUNT_MANAGER);
    $this->pv = responsavel('PV', Funcao::PRE_VENDAS);

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

    // RN-16: reabrir a concluída apaga o término e reabre o projeto
    $this->patchJson("/api/v1/projetos/{$id}/atividades/{$a1}/status", ['status' => 'Em Andamento'])->assertOk();
    expect(DB::table('atividades')->where('id', $a1)->value('data_termino'))->toBeNull();
    $this->assertDatabaseHas('projetos', ['id' => $id, 'status' => 'Em Andamento']);

    // RN-16: não se muda para o próprio status
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

function edicaoPayload($test, array $extra = []): array
{
    return $extra + [
        'descricao' => 'Kickoff remarcado',
        'tipo' => 'Mapeamento',
        'data_entrada' => '2026-09-30',
        'data_limite' => '2026-10-20',
        'account_manager_id' => $test->am->id,
        'pre_vendas_id' => $test->pv->id,
        'observacao' => 'Cliente pediu nova data',
    ];
}

it('edita atividade e mantém o status (RN-28)', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))->json('id');

    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['status' => 'Concluída']))->assertOk();

    $this->assertDatabaseHas('atividades', [
        'id' => $a, 'descricao' => 'Kickoff remarcado', 'tipo_id' => tipoAtividadeId('Mapeamento'), 'status' => 'Não Iniciada',
        'observacao' => 'Cliente pediu nova data', 'sequencia' => 1,
    ]);
    expect(DB::table('atividades')->where('id', $a)->value('data_limite'))->toStartWith('2026-10-20');
});

it('edita atividade concluída corrigindo o término (RN-28)', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this, ['status' => 'Concluída']))->json('id');

    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['data_termino' => '2026-10-01']))->assertOk();

    expect(DB::table('atividades')->where('id', $a)->value('data_termino'))->toStartWith('2026-10-01');
    $this->assertDatabaseHas('projetos', ['id' => $id, 'status' => 'Concluído']);
});

it('rejeita edição com PV sem o papel e com datas inválidas (RN-25/RN-18)', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))->json('id');

    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['pre_vendas_id' => $this->am->id]))
        ->assertUnprocessable();
    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['data_limite' => '2026-09-01']))
        ->assertUnprocessable();
    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['account_manager_id' => null]))
        ->assertUnprocessable();

    $this->assertDatabaseHas('atividades', ['id' => $a, 'descricao' => 'Reunião de kickoff']);
});

it('permite editar atividade cujo AM ficou inativo, se ele não for trocado', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))->json('id');
    $this->am->update(['ativo' => false]);

    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this))->assertOk();
});

it('não edita atividade de projeto cancelado (RN-11)', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this))->json('id');
    $this->postJson("/api/v1/projetos/{$id}/cancelar")->assertOk();

    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this))->assertUnprocessable();
});

it('troca o cliente do projeto (RN-29) e rejeita cliente inativo (RN-06)', function () {
    $id = criarProjetoViaApi($this);
    $novo = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Novo Cliente S.A.'));
    $inativo = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Inativo S.A.'));
    DB::table('clientes')->where('id', $inativo)->update(['ativo' => false]);

    $this->patchJson("/api/v1/projetos/{$id}/cliente", ['cliente_id' => $novo])->assertOk();
    $this->assertDatabaseHas('projetos', ['id' => $id, 'cliente_id' => $novo]);

    $this->patchJson("/api/v1/projetos/{$id}/cliente", ['cliente_id' => $inativo])->assertUnprocessable();
    $this->patchJson("/api/v1/projetos/{$id}/cliente", ['cliente_id' => (string) Str::uuid()])->assertUnprocessable();
    $this->assertDatabaseHas('projetos', ['id' => $id, 'cliente_id' => $novo]);
});

it('não troca cliente de projeto cancelado (RN-11/RN-29)', function () {
    $id = criarProjetoViaApi($this);
    $novo = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Novo Cliente S.A.'));
    $this->postJson("/api/v1/projetos/{$id}/cancelar")->assertOk();

    $this->patchJson("/api/v1/projetos/{$id}/cliente", ['cliente_id' => $novo])->assertUnprocessable();
});

it('RN-20: com login, só o autor altera a observação da atividade', function () {
    $id = criarProjetoViaApi($this);
    $a = $this->postJson("/api/v1/projetos/{$id}/atividades", atividadePayload($this, ['observacao' => 'Do admin']))->json('id');
    $this->assertDatabaseHas('atividades', ['id' => $a, 'observacao_autor_id' => $this->usuarioLogado->id]);

    Sanctum::actingAs(User::factory()->create()); // outro usuário (comum)
    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['observacao' => 'Intruso']))
        ->assertUnprocessable()->assertJsonPath('error', 'Somente o autor pode alterar a observação desta atividade.');
    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['observacao' => 'Do admin']))->assertOk(); // manter é permitido

    Sanctum::actingAs($this->usuarioLogado);
    $this->putJson("/api/v1/projetos/{$id}/atividades/{$a}", edicaoPayload($this, ['observacao' => 'Atualizada pelo autor']))->assertOk();
});
