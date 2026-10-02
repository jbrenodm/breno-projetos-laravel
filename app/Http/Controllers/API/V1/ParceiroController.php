<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\AlterarSituacaoRequest;
use App\Http\Requests\API\V1\CadastrarParceiroRequest;
use App\Http\Requests\API\V1\EditarParceiroRequest;
use App\Http\Requests\API\V1\EditarSolucaoRequest;
use Illuminate\Http\JsonResponse;
use Src\Parceiros\Application\DTOs\AlterarSituacaoInput;
use Src\Parceiros\Application\DTOs\AlterarSituacaoSolucaoInput;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\DTOs\EditarClienteInput;
use Src\Parceiros\Application\DTOs\EditarFornecedorInput;
use Src\Parceiros\Application\DTOs\EditarSolucaoInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\AlterarSituacaoCliente;
use Src\Parceiros\Application\UseCases\AlterarSituacaoFornecedor;
use Src\Parceiros\Application\UseCases\AlterarSituacaoSolucao;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Parceiros\Application\UseCases\EditarCliente;
use Src\Parceiros\Application\UseCases\EditarFornecedor;
use Src\Parceiros\Application\UseCases\EditarSolucao;
use Symfony\Component\HttpFoundation\Response;

final class ParceiroController extends Controller
{
    public function listarClientes(ParceirosQuery $query): JsonResponse
    {
        return response()->json(['data' => $query->listarClientes()]);
    }

    public function cadastrarCliente(CadastrarParceiroRequest $request, CadastrarCliente $useCase): JsonResponse
    {
        $id = $useCase->execute(new CadastrarClienteInput(
            $request->validated('razao_social'),
            $request->validated('nome_fantasia'),
            $request->validated('cnpj'),
        ));

        return response()->json(['success' => true, 'id' => $id], Response::HTTP_CREATED);
    }

    public function listarFornecedores(ParceirosQuery $query): JsonResponse
    {
        return response()->json(['data' => $query->listarFornecedores()]);
    }

    public function cadastrarFornecedor(CadastrarParceiroRequest $request, CadastrarFornecedor $useCase): JsonResponse
    {
        $id = $useCase->execute(new CadastrarFornecedorInput(
            $request->validated('razao_social'),
            $request->validated('nome_fantasia'),
            $request->validated('cnpj'),
            $request->validated('solucoes') ?? [],
        ));

        return response()->json(['success' => true, 'id' => $id], Response::HTTP_CREATED);
    }

    public function editarCliente(string $clienteId, EditarParceiroRequest $request, EditarCliente $useCase): JsonResponse
    {
        $useCase->execute(new EditarClienteInput(
            $clienteId,
            $request->validated('razao_social'),
            $request->validated('nome_fantasia'),
            $request->validated('cnpj'),
        ));

        return response()->json(['success' => true]);
    }

    public function alterarSituacaoCliente(string $clienteId, AlterarSituacaoRequest $request, AlterarSituacaoCliente $useCase): JsonResponse
    {
        $useCase->execute(new AlterarSituacaoInput($clienteId, $request->boolean('ativo')));

        return response()->json(['success' => true]);
    }

    public function editarFornecedor(string $fornecedorId, EditarParceiroRequest $request, EditarFornecedor $useCase): JsonResponse
    {
        $useCase->execute(new EditarFornecedorInput(
            $fornecedorId,
            $request->validated('razao_social'),
            $request->validated('nome_fantasia'),
            $request->validated('cnpj'),
        ));

        return response()->json(['success' => true]);
    }

    public function alterarSituacaoFornecedor(string $fornecedorId, AlterarSituacaoRequest $request, AlterarSituacaoFornecedor $useCase): JsonResponse
    {
        $useCase->execute(new AlterarSituacaoInput($fornecedorId, $request->boolean('ativo')));

        return response()->json(['success' => true]);
    }

    public function editarSolucao(string $fornecedorId, string $solucaoId, EditarSolucaoRequest $request, EditarSolucao $useCase): JsonResponse
    {
        $useCase->execute(new EditarSolucaoInput($fornecedorId, $solucaoId, $request->validated('nome'), $request->validated('descricao')));

        return response()->json(['success' => true]);
    }

    public function alterarSituacaoSolucao(string $fornecedorId, string $solucaoId, AlterarSituacaoRequest $request, AlterarSituacaoSolucao $useCase): JsonResponse
    {
        $useCase->execute(new AlterarSituacaoSolucaoInput($fornecedorId, $solucaoId, $request->boolean('ativo')));

        return response()->json(['success' => true]);
    }
}
