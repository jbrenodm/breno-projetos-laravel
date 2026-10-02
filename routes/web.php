<?php

use App\Livewire\Dashboards\PainelOperacional;
use App\Livewire\Dashboards\TodasAsAtividades;
use App\Livewire\Parceiros\Clientes;
use App\Livewire\Parceiros\Fornecedores;
use App\Livewire\Projetos\DetalheProjeto;
use App\Livewire\Projetos\PainelProjetos;
use Illuminate\Support\Facades\Route;

// TODO (Roadmap fase 3): envolver em middleware('auth') quando o login existir.
Route::livewire('/', PainelProjetos::class)->name('projetos.index');
Route::livewire('/projetos/{projetoId}', DetalheProjeto::class)->name('projetos.show');
Route::livewire('/clientes', Clientes::class)->name('clientes.index');
Route::livewire('/fornecedores', Fornecedores::class)->name('fornecedores.index');
Route::livewire('/dashboards/operacional', PainelOperacional::class)->name('dashboards.operacional');
Route::livewire('/dashboards/atividades', TodasAsAtividades::class)->name('dashboards.atividades');
