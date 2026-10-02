<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** REQUISITOS.md §6 — roles e role_user (RN-25) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome')->unique();
            $table->string('descricao');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
