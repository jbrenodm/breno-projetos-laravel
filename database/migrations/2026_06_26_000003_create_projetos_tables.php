<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** REQUISITOS.md §6 — projetos, projeto_fornecedores, atividades */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('codigo_oportunidade', 50)->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('projeto_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignUuid('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();
            $table->foreignUuid('solucao_id')->nullable()->constrained('solucoes')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('atividades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->unsignedInteger('sequencia'); // ordem cronológica de criação no projeto (RN-14)
            $table->text('descricao');
            $table->string('tipo');
            $table->string('status');
            $table->date('data_entrada');
            $table->date('data_limite');
            $table->date('data_inicio')->nullable();
            $table->date('data_termino')->nullable();
            $table->foreignUuid('account_manager_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('pre_vendas_id')->constrained('users')->restrictOnDelete();
            $table->text('observacao')->nullable();
            $table->foreignUuid('observacao_autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['projeto_id', 'sequencia']);
            $table->index('account_manager_id');
            $table->index('pre_vendas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atividades');
        Schema::dropIfExists('projeto_fornecedores');
        Schema::dropIfExists('projetos');
    }
};
