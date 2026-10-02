<?php

use App\Http\Controllers\API\V1\ParceiroController;
use App\Http\Controllers\API\V1\ProjetoController;
use Illuminate\Support\Facades\Route;

// TODO (Roadmap fase 3): proteger com autenticação (Sanctum) quando o login existir.
Route::prefix('v1')->group(function (): void {
    Route::get('projetos', [ProjetoController::class, 'listar']);
    Route::post('projetos', [ProjetoController::class, 'registrar']);
    Route::get('projetos/{projetoId}', [ProjetoController::class, 'detalhar']);
    Route::post('projetos/{projetoId}/cancelar', [ProjetoController::class, 'cancelar']);
    Route::post('projetos/{projetoId}/atividades', [ProjetoController::class, 'registrarAtividade']);
    Route::patch('projetos/{projetoId}/atividades/{atividadeId}/status', [ProjetoController::class, 'alterarStatusAtividade']);

    Route::get('clientes', [ParceiroController::class, 'listarClientes']);
    Route::post('clientes', [ParceiroController::class, 'cadastrarCliente']);
    Route::get('fornecedores', [ParceiroController::class, 'listarFornecedores']);
    Route::post('fornecedores', [ParceiroController::class, 'cadastrarFornecedor']);
});
