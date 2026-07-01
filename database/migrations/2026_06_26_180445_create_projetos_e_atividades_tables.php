<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo_oportunidade')->nullable();
            $table->string('status');
            $table->uuid('cliente_id');
            $table->timestamps();
            
            $table->index('cliente_id');
        });

        Schema::create('projeto_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->uuid('fornecedor_id');
            $table->uuid('solucao_id')->nullable();
            $table->timestamps();
        });

        Schema::create('atividades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->text('descricao');
            $table->string('status');
            $table->dateTime('data_entrada');
            $table->dateTime('deadline');
            $table->dateTime('data_termino')->nullable();
            $table->uuid('account_manager_id');
            $table->uuid('pre_vendas_id');
            $table->timestamps();

            $table->index(['account_manager_id', 'pre_vendas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividades');
        Schema::dropIfExists('projeto_fornecedores');
        Schema::dropIfExists('projetos');
    }
};
