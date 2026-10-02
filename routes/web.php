<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Admin\Usuarios;
use App\Livewire\Auth\EsqueciSenha;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RedefinirSenha;
use App\Livewire\Auth\TrocarSenhaObrigatoria;
use App\Livewire\Conta\MinhaConta;
use App\Livewire\Dashboards\PainelOperacional;
use App\Livewire\Dashboards\PrazosEEntrega;
use App\Livewire\Dashboards\TodasAsAtividades;
use App\Livewire\Parceiros\Clientes;
use App\Livewire\Parceiros\Fornecedores;
use App\Livewire\Projetos\DetalheProjeto;
use App\Livewire\Projetos\PainelProjetos;
use Illuminate\Support\Facades\Route;

// RN-33/RN-37: telas para quem ainda não entrou.
Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/esqueci-senha', EsqueciSenha::class)->name('password.request');
    Route::livewire('/redefinir-senha/{token}', RedefinirSenha::class)->name('password.reset');
});

Route::middleware(['auth', 'ativo'])->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::livewire('/trocar-senha', TrocarSenhaObrigatoria::class)->name('senha.trocar'); // RN-36

    // RN-34: todo o sistema exige login (e a troca da senha temporária, RN-36).
    Route::middleware('troca-senha')->group(function (): void {
        Route::livewire('/', PainelProjetos::class)->name('projetos.index');
        Route::livewire('/projetos/{projetoId}', DetalheProjeto::class)->name('projetos.show');
        Route::livewire('/clientes', Clientes::class)->name('clientes.index');
        Route::livewire('/fornecedores', Fornecedores::class)->name('fornecedores.index');
        Route::livewire('/dashboards/operacional', PainelOperacional::class)->name('dashboards.operacional');
        Route::livewire('/dashboards/prazos', PrazosEEntrega::class)->name('dashboards.prazos');
        Route::livewire('/dashboards/atividades', TodasAsAtividades::class)->name('dashboards.atividades');
        Route::livewire('/minha-conta', MinhaConta::class)->name('conta'); // RN-40
        Route::livewire('/usuarios', Usuarios::class)->name('usuarios.index')->middleware('can:admin-geral'); // RN-35
    });
});
