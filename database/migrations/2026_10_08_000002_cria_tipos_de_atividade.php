<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * RN-19/RN-43 (REQUISITOS.md §4.8 e §6): tipos de atividade deixam de ser uma lista fixa e viram um cadastro.
 * atividades.tipo (texto) vira atividades.tipo_id (referência), para que renomear um tipo valha em todo o sistema.
 */
return new class extends Migration
{
    private const TIPOS_INICIAIS = ['Mapeamento', 'Homologação', 'Implantação', 'Comercial'];

    public function up(): void
    {
        Schema::create('tipos_atividade', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome', 60);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX tipos_atividade_nome_unico ON tipos_atividade (LOWER(nome))');

        // Os tipos iniciais e qualquer outro texto que já esteja gravado em atividades.
        $agora = now();
        $nomes = collect(self::TIPOS_INICIAIS)->merge(DB::table('atividades')->distinct()->pluck('tipo'))
            ->unique(fn (string $n) => mb_strtolower($n));
        $ids = $nomes->mapWithKeys(fn (string $nome) => [$nome => (string) Str::uuid()]);
        DB::table('tipos_atividade')->insert($ids->map(fn (string $id, string $nome) => [
            'id' => $id, 'nome' => $nome, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
        ])->values()->all());

        Schema::table('atividades', fn (Blueprint $table) => $table->uuid('tipo_id')->nullable()->after('descricao'));
        DB::statement('UPDATE atividades a SET tipo_id = t.id FROM tipos_atividade t WHERE LOWER(t.nome) = LOWER(a.tipo)');

        Schema::table('atividades', function (Blueprint $table) {
            $table->uuid('tipo_id')->nullable(false)->change();
            $table->foreign('tipo_id')->references('id')->on('tipos_atividade')->restrictOnDelete();
            $table->index('tipo_id');
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('atividades', fn (Blueprint $table) => $table->string('tipo')->nullable()->after('descricao'));
        DB::statement('UPDATE atividades a SET tipo = t.nome FROM tipos_atividade t WHERE t.id = a.tipo_id');

        Schema::table('atividades', function (Blueprint $table) {
            $table->string('tipo')->nullable(false)->change();
            $table->dropForeign(['tipo_id']);
            $table->dropColumn('tipo_id');
        });

        Schema::dropIfExists('tipos_atividade');
    }
};
