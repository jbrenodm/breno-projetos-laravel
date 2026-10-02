<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Infrastructure\Persistence\RoleModel;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Parceiros\Infrastructure\Persistence\Eloquent\Models\ClienteModel;

/**
 * Dados para desenvolvimento local (NÃO usar em produção).
 * Usuários: senha "password".
 */
class DadosDeExemploSeeder extends Seeder
{
    public function run(CadastrarCliente $cadastrarCliente, CadastrarFornecedor $cadastrarFornecedor): void
    {
        $usuarios = [
            ['Breno (Admin)', 'admin@breno.local', [Papel::ADMIN_GERAL, Papel::PRE_VENDAS]],
            ['Ana Account Manager', 'ana.am@breno.local', [Papel::ACCOUNT_MANAGER]],
            ['Carlos Account Manager', 'carlos.am@breno.local', [Papel::ACCOUNT_MANAGER]],
            ['Paula Pré-vendas', 'paula.pv@breno.local', [Papel::PRE_VENDAS]],
            ['Rafael Pré-vendas', 'rafael.pv@breno.local', [Papel::PRE_VENDAS]],
        ];

        foreach ($usuarios as [$nome, $email, $papeis]) {
            $user = User::query()->firstOrCreate(['email' => $email], ['name' => $nome, 'password' => 'password', 'ativo' => true]);
            $ids = RoleModel::query()->whereIn('nome', array_map(fn (Papel $p) => $p->value, $papeis))->pluck('id');
            $user->roles()->syncWithoutDetaching($ids);
        }

        if (ClienteModel::query()->exists()) {
            return; // já semeado
        }

        $cadastrarCliente->execute(new CadastrarClienteInput('Banco Exemplo S.A.', 'Banco Exemplo', '11.222.333/0001-81'));
        $cadastrarCliente->execute(new CadastrarClienteInput('Prefeitura Municipal de Exemplo'));

        $cadastrarFornecedor->execute(new CadastrarFornecedorInput('Fornecedor Alpha Tecnologia Ltda', 'Alpha', null, ['Firewall NGFW', 'EDR']));
        $cadastrarFornecedor->execute(new CadastrarFornecedorInput('Beta Serviços de TI Ltda', 'Beta Serviços', null, ['Implantação', 'Suporte 8x5']));
    }
}
