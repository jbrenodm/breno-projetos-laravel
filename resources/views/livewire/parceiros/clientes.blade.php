<div>
    <h1 class="h3 mb-3">Clientes</h1>

    @if (session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form class="card shadow-sm" wire:submit="salvar">
                <div class="card-header fw-semibold">Novo cliente</div>
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
                    <button class="btn btn-primary w-100">Cadastrar</button>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <table class="table mb-0 align-middle">
                    <thead class="table-light"><tr><th>Nome</th><th>Razão social</th><th>CNPJ</th><th>Situação</th></tr></thead>
                    <tbody>
                        @forelse ($clientes as $c)
                            <tr wire:key="cliente-{{ $c['id'] }}">
                                <td class="fw-semibold">{{ $c['nome'] }}</td>
                                <td>{{ $c['razao_social'] }}</td>
                                <td>{{ $c['cnpj'] ?? '—' }}</td>
                                <td>{!! $c['ativo'] ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum cliente cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
