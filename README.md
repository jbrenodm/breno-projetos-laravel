# breno-projetos-laravel

Gestão de Projetos comerciais e suas Atividades (Account Managers e Pré-vendas).

- **Requisitos e arquitetura (fonte da verdade):** [`docs/REQUISITOS.md`](docs/REQUISITOS.md)
- **Regras para quem (ou qual IA) for mexer no código:** [`AGENTS.md`](AGENTS.md)

## Stack

Laravel 13 · PHP 8.3+ · PostgreSQL · Livewire 4 + Alpine.js + Bootstrap 5 · Pest 4 · Clean Architecture + DDD (`src/`).

## Ambiente de desenvolvimento (Ubuntu Server 26.04)

```bash
git clone https://github.com/jbrenodm/breno-projetos-laravel.git
cd breno-projetos-laravel
bash scripts/setup-ubuntu.sh          # instala PHP, Composer, PostgreSQL, cria bancos, .env, migra e testa
php artisan serve --host=0.0.0.0      # acesse http://IP-DA-VM:8000 pelo Windows
```

## Ambiente com Docker

Sobe o Laravel (PHP 8.5) e um PostgreSQL 18 próprio, sem depender do PHP/PostgreSQL da VM.
O projeto é montado no container, então as alterações no código valem na hora.

```bash
cp -n .env.example .env               # se ainda não existir (o banco é configurado pelo docker-compose.yml)
docker compose up -d --build          # acesse http://IP-DA-VM:8000
docker compose exec app scripts/iniciar-do-zero.sh   # 1ª vez: cria papéis e o primeiro Admin
```

| Ação | Comando |
|---|---|
| Rodar os testes | `docker compose exec app php artisan test` |
| Qualquer comando artisan | `docker compose exec app php artisan ...` |
| Ver logs | `docker compose logs -f app` |
| Parar | `docker compose down` (os dados ficam no volume `pgdata`) |
| Acessar o banco pela VM | `psql -h 127.0.0.1 -p 5433 -U laravel breno_projetos_laravel` (senha: `secret`) |

## Comandos do dia a dia

| Ação | Comando |
|---|---|
| Rodar todos os testes | `php artisan test` |
| Só domínio / arquitetura | `php artisan test --testsuite=Unit,Arch` |
| Recriar banco com dados de exemplo | `php artisan migrate:fresh --seed` |
| Formatar código | `./vendor/bin/pint` |

## Estrutura

```
src/
├── Shared/      Portas comuns (GeradorDeId, Relogio) e exceção base de regra de negócio
├── Projetos/    Contexto core: Projeto (aggregate root) e Atividade
├── Parceiros/   Clientes, Fornecedores e Soluções
└── Identidade/  Papéis de usuário (AM, PV, Admin)
app/
├── Livewire/    Telas (chamam Casos de Uso e Queries — nunca o Eloquent direto)
└── Http/        API v1 (Controllers finos + FormRequests)
tests/
├── Arch/        Guardiões da arquitetura
├── Unit/        Regras de domínio
└── Feature/     API e telas (PostgreSQL de teste)
```
