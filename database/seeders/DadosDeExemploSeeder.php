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
use Src\Responsaveis\Application\DTOs\CadastrarResponsavelInput;
use Src\Responsaveis\Application\UseCases\CadastrarResponsavel;
use Src\Responsaveis\Domain\Funcao;
use Src\Responsaveis\Infrastructure\Persistence\ResponsavelModel;

/**
 * Dados para desenvolvimento local (NÃO usar em produção).
 * Usuário Admin: admin@breno.local, senha "password". AM e PV são Responsáveis (RN-42), sem login.
 */
class DadosDeExemploSeeder extends Seeder
{
    public function run(CadastrarCliente $cadastrarCliente, CadastrarFornecedor $cadastrarFornecedor, CadastrarResponsavel $cadastrarResponsavel): void
    {
        $admin = User::query()->firstOrCreate(['email' => 'admin@breno.local'], ['name' => 'Breno (Admin)', 'password' => 'password', 'ativo' => true]);
        $admin->roles()->syncWithoutDetaching([RoleModel::garantir(Papel::ADMIN_GERAL)->id]);

        if (! ResponsavelModel::query()->exists()) {
            $responsaveis = [
                ['Ana Account Manager', 'ana.am@breno.local', [Funcao::ACCOUNT_MANAGER]],
                ['Carlos Account Manager', null, [Funcao::ACCOUNT_MANAGER]],
                ['Paula Pré-vendas', 'paula.pv@breno.local', [Funcao::PRE_VENDAS]],
                ['Rafael Pré-vendas', null, [Funcao::PRE_VENDAS]],
                ['Breno Muniz', null, [Funcao::ACCOUNT_MANAGER, Funcao::PRE_VENDAS]],
            ];

            foreach ($responsaveis as [$nome, $email, $funcoes]) {
                $cadastrarResponsavel->execute(new CadastrarResponsavelInput(
                    $admin->id, $nome, $email, array_map(fn (Funcao $f) => $f->value, $funcoes),
                ));
            }
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
