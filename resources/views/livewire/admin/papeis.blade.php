<div>
    <h1 class="h3 mb-1">Papéis</h1>
    <p class="text-muted">
        Altere o nome e a sigla com que cada papel aparece no sistema (telas, filtros, gráficos e mensagens).
        O que cada papel pode fazer não muda.
    </p>

    @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif
    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="card shadow-sm" style="max-width: 900px">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Nome exibido</th><th>Sigla</th><th>Identificador</th><th class="text-end">Usuários</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($papeis as $p)
                        <tr wire:key="papel-{{ $p['papel'] }}" @class(['table-primary' => $editando === $p['papel']])>
                            @if ($editando === $p['papel'])
                                <td colspan="2">
                                    <form class="row g-2" wire:submit="salvar" id="form-papel">
                                        <div class="col-sm-8">
                                            <input class="form-control form-control-sm @error('nome') is-invalid @enderror" wire:model="nome"
                                                   maxlength="60" aria-label="Nome exibido" autofocus>
                                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-sm-4">
                                            <input class="form-control form-control-sm @error('sigla') is-invalid @enderror" wire:model="sigla"
                                                   maxlength="10" placeholder="(opcional)" aria-label="Sigla">
                                            @error('sigla') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </form>
                                </td>
                            @else
                                <td class="fw-semibold">{{ $p['nome'] }}</td>
                                <td>{{ $p['sigla'] ?? '—' }}</td>
                            @endif
                            <td><code>{{ $p['papel'] }}</code></td>
                            <td class="text-end">{{ $p['usuarios'] }}</td>
                            <td class="text-end text-nowrap">
                                @if ($editando === $p['papel'])
                                    <button class="btn btn-sm btn-primary" form="form-papel">Salvar</button>
                                    <button type="button" class="btn btn-sm btn-light" wire:click="cancelar">Cancelar</button>
                                @else
                                    <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $p['papel'] }}')"><i class="bi bi-pencil"></i> Renomear</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>
        O identificador é interno e não muda. Criar papéis novos dependerá do controle de permissões, ainda não definido.</p>
</div>
