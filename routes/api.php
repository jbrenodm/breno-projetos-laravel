<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\ProjetoController;
use App\Http\Controllers\API\V1\FornecedorController;

// Rotas de Projetos
Route::post('/v1/projetos', [ProjetoController::class, 'registrarProjeto']);
Route::get('/v1/projetos/{projetoId}', [ProjetoController::class, 'detalharProjeto']);
Route::post('/v1/projetos/{projetoId}/atividades', [ProjetoController::class, 'registrarAtividade']);
Route::patch('/v1/projetos/{projetoId}/atividades/{atividadeId}/concluir', [ProjetoController::class, 'concluirAtividade']);

// Rotas de Fornecedores
Route::get('/v1/fornecedores', [FornecedorController::class, 'listar']);
Route::post('/v1/fornecedores', [FornecedorController::class, 'cadastrar']);