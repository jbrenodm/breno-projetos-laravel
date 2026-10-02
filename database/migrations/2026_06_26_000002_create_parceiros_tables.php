<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** REQUISITOS.md §6 — clientes, fornecedores, solucoes (RN-21..RN-24) */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['clientes', 'fornecedores'] as $tabela) {
            Schema::create($tabela, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('razao_social');
                $table->string('nome_fantasia')->nullable();
                $table->string('cnpj', 14)->nullable()->unique(); // NULLs múltiplos são permitidos no PostgreSQL
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('solucoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['fornecedor_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solucoes');
        Schema::dropIfExists('fornecedores');
        Schema::dropIfExists('clientes');
    }
};
