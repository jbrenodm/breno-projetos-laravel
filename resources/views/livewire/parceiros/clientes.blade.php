@php($editando = $editandoId !== null)
<div>
    <h1 class="h3 mb-3">Clientes</h1>

    @if (session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form @class(['card shadow-sm', 'border-primary' => $editando]) wire:submit="salvar">
                <div @class(['card-header fw-semibold', 'bg-primary-subtle' => $editando])>{{ $editando ? 'Editar cliente' : 'Novo cliente' }}</div>
                <div class="card-body">
                    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror
                    <div class="mb-3">
                        <label class="form-label">Razão social <span class="text-danger">*</span></label>
                        <input class="form-control @error('razaoSocial') is-invalid @enderror" wire:model="razaoSocial">
                        @error('razaoSocial') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome fantasia</label>
                        <input class="form-control" wire:model="nomeFantasia">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CNPJ</label>
                        <input class="form-control" wire:model="cnpj" placeholder="00.000.000/0000-00">
                    </div>
                    <button class="btn btn-primary w-100">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
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
                        <thead class="table-light"><tr><th>Nome</th><th>Razão social</th><th>CNPJ</th><th>Situação</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($clientes as $c)
                                <tr wire:key="cliente-{{ $c['id'] }}" @class(['table-primary' => $editandoId === $c['id'], 'text-body-secondary' => ! $c['ativo']])>
                                    <td class="fw-semibold">{{ $c['nome'] }}</td>
                                    <td>{{ $c['razao_social'] }}</td>
                                    <td class="text-nowrap">{{ $c['cnpj'] ?? '—' }}</td>
                                    <td>{!! $c['ativo'] ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' !!}</td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $c['id'] }}')" title="Editar"><i class="bi bi-pencil"></i> Editar</button>
                                        @if ($c['ativo'])
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="alterarSituacao('{{ $c['id'] }}', false)"
                                                    wire:confirm="Inativar este cliente? Ele deixa de aparecer para novos projetos; os projetos existentes não mudam.">Inativar</button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" wire:click="alterarSituacao('{{ $c['id'] }}', true)">Reativar</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum cliente cadastrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
