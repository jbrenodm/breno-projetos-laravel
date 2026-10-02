<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Rótulo do papel admin_geral passa a ser "Admin Geral do Sistema" (o identificador interno não muda). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('nome', 'admin_geral')->update(['descricao' => 'Admin Geral do Sistema']);
    }

    public function down(): void
    {
        DB::table('roles')->where('nome', 'admin_geral')->update(['descricao' => 'Admin Geral']);
    }
};
