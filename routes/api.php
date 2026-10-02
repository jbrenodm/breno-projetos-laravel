<?php

use App\Http\Controllers\API\V1\ParceiroController;
use App\Http\Controllers\API\V1\ProjetoController;
use Illuminate\Support\Facades\Route;

// RN-34: a API exige token pessoal (Sanctum), gerado em "Minha conta".
Route::prefix('v1')->middleware(['auth:sanctum', 'ativo', 'troca-senha'])->group(function (): void {
    Route::get('projetos', [ProjetoController::class, 'listar']);
    Route::post('projetos', [ProjetoController::class, 'registrar']);
    Route::get('projetos/{projetoId}', [ProjetoController::class, 'detalhar']);
    Route::post('projetos/{projetoId}/cancelar', [ProjetoController::class, 'cancelar']);
    Route::patch('projetos/{projetoId}/cliente', [ProjetoController::class, 'alterarCliente']);
    Route::post('projetos/{projetoId}/atividades', [ProjetoController::class, 'registrarAtividade']);
    Route::put('projetos/{projetoId}/atividades/{atividadeId}', [ProjetoController::class, 'editarAtividade']);
    Route::patch('projetos/{projetoId}/atividades/{atividadeId}/status', [ProjetoController::class, 'alterarStatusAtividade']);

    Route::get('clientes', [ParceiroController::class, 'listarClientes']);
    Route::post('clientes', [ParceiroController::class, 'cadastrarCliente']);
    Route::put('clientes/{clienteId}', [ParceiroController::class, 'editarCliente']);
    Route::patch('clientes/{clienteId}/situacao', [ParceiroController::class, 'alterarSituacaoCliente']);
    Route::get('fornecedores', [ParceiroController::class, 'listarFornecedores']);
    Route::post('fornecedores', [ParceiroController::class, 'cadastrarFornecedor']);
    Route::put('fornecedores/{fornecedorId}', [ParceiroController::class, 'editarFornecedor']);
    Route::patch('fornecedores/{fornecedorId}/situacao', [ParceiroController::class, 'alterarSituacaoFornecedor']);
    Route::put('fornecedores/{fornecedorId}/solucoes/{solucaoId}', [ParceiroController::class, 'editarSolucao']);
    Route::patch('fornecedores/{fornecedorId}/solucoes/{solucaoId}/situacao', [ParceiroController::class, 'alterarSituacaoSolucao']);
});
