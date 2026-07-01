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
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome_fantasia');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('solucoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fornecedor_id')->constrained('fornecedores')->onDelete('cascade');
            $table->string('nome');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solucoes');
        Schema::dropIfExists('fornecedores');
    }
};
