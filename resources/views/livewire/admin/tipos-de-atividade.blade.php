@php($editando = $editandoId !== null)
<div>
    <h1 class="h3 mb-1">Tipos de atividade</h1>
    <p class="text-muted mb-3">Renomear um tipo muda o nome em todas as atividades, filtros e gráficos. Tipos não são excluídos: inative os que não forem mais usados.</p>

    @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif
    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="row g-4">
        <div class="col-lg-4">
            <form @class(['card shadow-sm', 'border-primary' => $editando]) wire:submit="salvar">
                <div @class(['card-header fw-semibold', 'bg-primary-subtle' => $editando])>{{ $editando ? 'Renomear tipo' : 'Novo tipo' }}</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input class="form-control @error('nome') is-invalid @enderror" wire:model="nome" maxlength="60" autocomplete="off">
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary w-100">{{ $editando ? 'Salvar novo nome' : 'Cadastrar' }}</button>
                    @if ($editando)
                        <button type="button" class="btn btn-light w-100 mt-2" wire:click="cancelarEdicao">Cancelar</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light"><tr><th>Nome</th><th class="text-end">Atividades</th><th>Situação</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($tipos as $t)
                                <tr wire:key="tipo-{{ $t['id'] }}" @class(['table-primary' => $editandoId === $t['id'], 'text-body-secondary' => ! $t['ativo']])>
                                    <td class="fw-semibold">{{ $t['nome'] }}</td>
                                    <td class="text-end">{{ $t['atividades'] }}</td>
                                    <td>{!! $t['ativo'] ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' !!}</td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $t['id'] }}')"><i class="bi bi-pencil"></i> Renomear</button>
                                        @if ($t['ativo'])
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="alterarSituacao('{{ $t['id'] }}', false)"
                                                    wire:confirm="Inativar o tipo {{ $t['nome'] }}? Ele deixa de aparecer para novas atividades; as atividades existentes não mudam.">Inativar</button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" wire:click="alterarSituacao('{{ $t['id'] }}', true)">Reativar</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum tipo cadastrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
