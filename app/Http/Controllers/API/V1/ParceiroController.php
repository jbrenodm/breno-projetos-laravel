<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\CadastrarParceiroRequest;
use Illuminate\Http\JsonResponse;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
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
}
