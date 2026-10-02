<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\AlterarClienteDoProjetoRequest;
use App\Http\Requests\API\V1\AlterarStatusAtividadeRequest;
use App\Http\Requests\API\V1\EditarAtividadeRequest;
use App\Http\Requests\API\V1\RegistrarAtividadeRequest;
use App\Http\Requests\API\V1\RegistrarProjetoRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Src\Projetos\Application\DTOs\AlterarClienteDoProjetoInput;
use Src\Projetos\Application\DTOs\AlterarStatusAtividadeInput;
use Src\Projetos\Application\DTOs\CancelarProjetoInput;
use Src\Projetos\Application\DTOs\EditarAtividadeInput;
use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Application\UseCases\AlterarClienteDoProjeto;
use Src\Projetos\Application\UseCases\AlterarStatusAtividade;
use Src\Projetos\Application\UseCases\CancelarProjeto;
use Src\Projetos\Application\UseCases\EditarAtividade;
use Src\Projetos\Application\UseCases\RegistrarNovaAtividade;
use Src\Projetos\Application\UseCases\RegistrarNovoProjeto;
use Src\Shared\Application\RecursoNaoEncontradoException;
use Symfony\Component\HttpFoundation\Response;

/** Controller fino: valida a forma, monta o DTO e chama o caso de uso. */
final class ProjetoController extends Controller
{
    public function listar(ProjetoQuery $query): JsonResponse
    {
        return response()->json(['data' => $query->listar()]);
    }

    public function detalhar(string $projetoId, ProjetoQuery $query): JsonResponse
    {
        $projeto = $query->detalhar($projetoId) ?? throw RecursoNaoEncontradoException::para('Projeto', $projetoId);

        return response()->json(['data' => $projeto]);
    }

    public function registrar(RegistrarProjetoRequest $request, RegistrarNovoProjeto $useCase): JsonResponse
    {
        $id = $useCase->execute(new RegistrarProjetoInput(
            clienteId: $request->validated('cliente_id'),
            fornecedores: $request->validated('fornecedores'),
            codigoOportunidade: $request->validated('codigo_oportunidade'),
            usuarioExecutorId: $request->user()?->id,
        ));

        return response()->json(['success' => true, 'id' => $id], Response::HTTP_CREATED);
    }

    public function cancelar(string $projetoId, CancelarProjeto $useCase): JsonResponse
    {
        $useCase->execute(new CancelarProjetoInput($projetoId, request()->user()?->id));

        return response()->json(['success' => true]);
    }

    public function alterarCliente(string $projetoId, AlterarClienteDoProjetoRequest $request, AlterarClienteDoProjeto $useCase): JsonResponse
    {
        $useCase->execute(new AlterarClienteDoProjetoInput(
            projetoId: $projetoId,
            clienteId: $request->validated('cliente_id'),
            usuarioExecutorId: $request->user()?->id,
        ));

        return response()->json(['success' => true]);
    }

    public function registrarAtividade(string $projetoId, RegistrarAtividadeRequest $request, RegistrarNovaAtividade $useCase): JsonResponse
    {
        $id = $useCase->execute(new RegistrarAtividadeInput(
            projetoId: $projetoId,
            descricao: $request->validated('descricao'),
            tipo: $request->validated('tipo'),
            status: $request->validated('status'),
            dataEntrada: new DateTimeImmutable($request->validated('data_entrada')),
            dataLimite: new DateTimeImmutable($request->validated('data_limite')),
            dataInicio: self::dataOpcional($request->validated('data_inicio')),
            dataTermino: self::dataOpcional($request->validated('data_termino')),
            accountManagerId: $request->validated('account_manager_id'),
            preVendasId: $request->validated('pre_vendas_id'),
            observacao: $request->validated('observacao'),
            usuarioExecutorId: $request->user()?->id,
        ));

        return response()->json(['success' => true, 'id' => $id], Response::HTTP_CREATED);
    }

    public function editarAtividade(
        string $projetoId,
        string $atividadeId,
        EditarAtividadeRequest $request,
        EditarAtividade $useCase,
    ): JsonResponse {
        $useCase->execute(new EditarAtividadeInput(
            projetoId: $projetoId,
            atividadeId: $atividadeId,
            descricao: $request->validated('descricao'),
            tipo: $request->validated('tipo'),
            dataEntrada: new DateTimeImmutable($request->validated('data_entrada')),
            dataLimite: new DateTimeImmutable($request->validated('data_limite')),
            accountManagerId: $request->validated('account_manager_id'),
            preVendasId: $request->validated('pre_vendas_id'),
            dataInicio: self::dataOpcional($request->validated('data_inicio')),
            dataTermino: self::dataOpcional($request->validated('data_termino')),
            observacao: $request->validated('observacao'),
            usuarioExecutorId: $request->user()?->id,
        ));

        return response()->json(['success' => true]);
    }

    public function alterarStatusAtividade(
        string $projetoId,
        string $atividadeId,
        AlterarStatusAtividadeRequest $request,
        AlterarStatusAtividade $useCase,
    ): JsonResponse {
        $useCase->execute(new AlterarStatusAtividadeInput(
            projetoId: $projetoId,
            atividadeId: $atividadeId,
            novoStatus: $request->validated('status'),
            data: self::dataOpcional($request->validated('data')),
            usuarioExecutorId: $request->user()?->id,
        ));

        return response()->json(['success' => true]);
    }

    private static function dataOpcional(?string $valor): ?DateTimeImmutable
    {
        return $valor ? new DateTimeImmutable($valor) : null;
    }
}
