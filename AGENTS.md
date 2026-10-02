# Instruções para assistentes de IA (e humanos)

1. **Leia `docs/REQUISITOS.md` antes de qualquer alteração.** Ele é a fonte da verdade.
2. Não invente campos, status ou regras. Se algo não estiver no documento, pergunte e registre a decisão lá primeiro.
3. Escritas sempre passam por um Caso de Uso (`src/*/Application/UseCases`). Livewire e Controllers não gravam via Eloquent.
4. `src/*/Domain` e `src/*/Application` não podem usar Laravel (`Illuminate\*`, `collect()`, `Str`, `now()`...). Os testes de arquitetura (`tests/Arch`) quebram se isso acontecer.
5. Toda regra de negócio nova vem com teste Pest. Rode `php artisan test` antes de commitar.
6. Livewire é a versão 4, com componentes de **classe** (`app/Livewire/...`) e views em `resources/views/livewire/...`. Não usar componentes de arquivo único (⚡).
7. Nomes em português, alinhados à linguagem ubíqua (ex.: `data_limite`, nunca `deadline`).
