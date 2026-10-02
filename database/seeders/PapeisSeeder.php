<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Infrastructure\Persistence\RoleModel;

/** RN-25: papéis fixos do sistema. Seguro rodar várias vezes. */
class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Papel::cases() as $papel) {
            RoleModel::garantir($papel); // só cria o que falta: não desfaz nomes editados na tela (RN-41)
        }
    }
}
