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
            RoleModel::query()->updateOrCreate(['nome' => $papel->value], ['descricao' => $papel->rotulo()]);
        }
    }
}
