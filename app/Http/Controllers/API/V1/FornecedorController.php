<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Src\Fornecedores\Application\UseCases\CadastrarFornecedor;
use Src\Fornecedores\Application\DTOs\CadastrarFornecedorInput;
use Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel;
use App\Http\Resources\V1\FornecedorResource;

final class FornecedorController extends Controller
{
    public function __construct(
        private readonly CadastrarFornecedor $cadastrarFornecedor
    ) {}

    /**
     * Rota de Consulta - Retorna o catálogo completo de fornecedores e soluções
     */
    public function listar(): AnonymousResourceCollection
    {
        $fornecedores = FornecedorEloquentModel::with('solucoes')
            ->where('ativo', true)
            ->orderBy('nome_fantasia')
            ->get();

        return FornecedorResource::collection($fornecedores);
    }

    public function cadastrar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome_fantasia' => ['required', 'string', 'min:3', 'max:255'],
            'solucoes' => ['nullable', 'array'],
            'solucoes.*' => ['required', 'string', 'min:2'],
        ]);

        $input = new CadastrarFornecedorInput(
            nomeFantasia: $validated['nome_fantasia'],
            solucoes: $validated['solucoes'] ?? []
        );

        $fornecedorId = $this->cadastrarFornecedor->execute($input);

        return response()->json([
            'success' => true,
            'message' => 'Fornecedor cadastrado com sucesso no catálogo.',
            'id' => $fornecedorId
        ], Response::HTTP_CREATED);
    }
}