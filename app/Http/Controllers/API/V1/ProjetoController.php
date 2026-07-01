<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Src\Projetos\Application\UseCases\RegistrarNovaAtividade;
use Src\Projetos\Application\UseCases\RegistrarNovoProjeto;
use Src\Projetos\Application\UseCases\ConcluirAtividade;
use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Application\DTOs\ConcluirAtividadeInput;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use App\Http\Resources\V1\ProjetoResource;
use DateTimeImmutable;

final class ProjetoController extends Controller
{
    public function __construct(
        private readonly RegistrarNovaAtividade $registrarNovaAtividade,
        private readonly RegistrarNovoProjeto $registrarNovoProjeto,
        private readonly ConcluirAtividade $concluirAtividade
    ) {}

    public function detalharProjeto(string $projetoId): JsonResponse|ProjetoResource
    {
        $projeto = ProjetoEloquentModel::with(['fornecedores', 'atividades'])->find($projetoId);

        if ($projeto === null) {
            return response()->json([
                'success' => false,
                'error' => "Projeto com ID {$projetoId} não foi encontrado."
            ], Response::HTTP_NOT_FOUND);
        }

        return new ProjetoResource($projeto);
    }

    public function registrarProjeto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'uuid'],
            'codigo_oportunidade' => ['nullable', 'string'],
            'fornecedores' => ['required', 'array', 'min:1'],
            'fornecedores.*.fornecedor_id' => ['required', 'uuid'],
            'fornecedores.*.solucao_id' => ['nullable', 'uuid'],
        ]);

        // Sem try/catch! Se falhar no construtor da Entidade, o Laravel captura globalmente.
        $projetoId = $this->registrarNovoProjeto->execute(
            new RegistrarProjetoInput(
                clienteId: $validated['cliente_id'],
                fornecedores: $validated['fornecedores'],
                codigoOportunidade: $validated['codigo_oportunidade'] ?? null
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Projeto inicializado com sucesso.',
            'id' => $projetoId
        ], Response::HTTP_CREATED);
    }

    public function concluirAtividade(string $projetoId, string $atividadeId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data_termino' => ['required', 'date'],
        ]);

        $this->concluirAtividade->execute(
            new ConcluirAtividadeInput(
                projetoId: $projetoId,
                atividadeId: $atividadeId,
                dataTermino: new DateTimeImmutable($validated['data_termino'])
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Atividade concluída com sucesso.'
        ], Response::HTTP_OK);
    }

    public function registrarAtividade(string $projetoId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descricao' => ['required', 'string', 'min:3'],
            'status' => ['required', 'string'],
            'data_entrada' => ['required', 'date'],
            'deadline' => ['required', 'date'],
            'account_manager_id' => ['nullable', 'string'],
            'pre_vendas_id' => ['nullable', 'string'],
        ]);

        // Sem try/catch! O fluxo segue direto e limpo.
        $this->registrarNovaAtividade->execute(
            new RegistrarAtividadeInput(
                projetoId: $projetoId,
                descricao: $validated['descricao'],
                statusAtividade: $validated['status'],
                dataEntrada: new DateTimeImmutable($validated['data_entrada']),
                deadline: new DateTimeImmutable($validated['deadline']),
                accountManagerId: $validated['account_manager_id'] ?? null,
                preVendasId: $validated['pre_vendas_id'] ?? null
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Atividade registrada com sucesso.'
        ], Response::HTTP_CREATED);
    }
}