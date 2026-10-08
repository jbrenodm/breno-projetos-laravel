<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-42 (REQUISITOS.md §4.7 e §6): AM e PV deixam de ser usuários e viram Responsáveis.
 * Os responsáveis migrados mantêm o mesmo id do usuário de origem, então as atividades continuam apontando para a mesma pessoa.
 */
return new class extends Migration
{
    private const FUNCOES = ['account_manager', 'pre_vendas'];

    public function up(): void
    {
        Schema::create('responsaveis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('email')->nullable()->unique(); // guardado em minúsculas; vários sem e-mail são permitidos
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('responsavel_funcoes', function (Blueprint $table) {
            $table->foreignUuid('responsavel_id')->constrained('responsaveis')->cascadeOnDelete();
            $table->string('funcao', 30);
            $table->primary(['responsavel_id', 'funcao']);
        });

        // Funções de cada usuário: os papéis AM/PV que ele tem e as que ele exerce em atividades (mesmo que o papel tenha sido retirado depois).
        $funcoes = [];
        DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->whereIn('roles.nome', self::FUNCOES)
            ->get(['role_user.user_id', 'roles.nome'])
            ->each(function ($l) use (&$funcoes) {
                $funcoes[$l->user_id][$l->nome] = true;
            });
        DB::table('atividades')->distinct()->pluck('account_manager_id')
            ->each(function ($id) use (&$funcoes) {
                $funcoes[$id]['account_manager'] = true;
            });
        DB::table('atividades')->distinct()->pluck('pre_vendas_id')
            ->each(function ($id) use (&$funcoes) {
                $funcoes[$id]['pre_vendas'] = true;
            });

        $agora = now();
        DB::table('users')->whereIn('id', array_keys($funcoes))->orderBy('name')
            ->get(['id', 'name', 'email', 'ativo'])
            ->each(function ($u) use ($funcoes, $agora) {
                DB::table('responsaveis')->insert([
                    'id' => $u->id, 'nome' => $u->name, 'email' => mb_strtolower($u->email), 'ativo' => $u->ativo,
                    'created_at' => $agora, 'updated_at' => $agora,
                ]);
                foreach (array_keys($funcoes[$u->id]) as $funcao) {
                    DB::table('responsavel_funcoes')->insert(['responsavel_id' => $u->id, 'funcao' => $funcao]);
                }
            });

        Schema::table('atividades', function (Blueprint $table) {
            $table->dropForeign(['account_manager_id']);
            $table->dropForeign(['pre_vendas_id']);
            $table->foreign('account_manager_id')->references('id')->on('responsaveis')->restrictOnDelete();
            $table->foreign('pre_vendas_id')->references('id')->on('responsaveis')->restrictOnDelete();
        });

        // RN-25: usuário só tem o papel admin_geral (ou nenhum). As linhas de AM/PV em "roles" ficam: guardam nome e sigla das funções (RN-41).
        DB::table('role_user')
            ->whereIn('role_id', DB::table('roles')->whereIn('nome', self::FUNCOES)->select('id'))
            ->delete();
    }

    public function down(): void
    {
        $roles = DB::table('roles')->whereIn('nome', self::FUNCOES)->pluck('id', 'nome');

        DB::table('responsavel_funcoes')
            ->join('users', 'users.id', '=', 'responsavel_funcoes.responsavel_id')
            ->get(['responsavel_funcoes.responsavel_id', 'responsavel_funcoes.funcao'])
            ->each(fn ($l) => isset($roles[$l->funcao]) && DB::table('role_user')->insertOrIgnore([
                'user_id' => $l->responsavel_id, 'role_id' => $roles[$l->funcao],
            ]));

        Schema::table('atividades', function (Blueprint $table) {
            $table->dropForeign(['account_manager_id']);
            $table->dropForeign(['pre_vendas_id']);
            // Só volta se todo responsável usado em atividades ainda existir como usuário.
            $table->foreign('account_manager_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('pre_vendas_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::dropIfExists('responsavel_funcoes');
        Schema::dropIfExists('responsaveis');
    }
};
