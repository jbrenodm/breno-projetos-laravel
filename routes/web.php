<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Projetos\Dashboard;
use App\Livewire\Projetos\Detalhes;

Route::get('/', function () {
    return view('welcome');
});

// Nova Rota Dedicada para carregar como Full-Page Component
Route::get('/projetos/{id}', function (string $id) {
    return view('projeto-detalhes', ['id' => $id]);
});
