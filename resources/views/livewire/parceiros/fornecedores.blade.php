<div>
    <h1 class="h3 mb-3">Fornecedores e soluções</h1>

    @if (session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form class="card shadow-sm" wire:submit="salvar">
                <div class="card-header fw-semibold">Novo fornecedor</div>
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
                    <div class="mb-3">
                        <label class="form-label">Soluções <small class="text-muted">(opcional, uma por linha)</small></label>
                        <textarea class="form-control" rows="3" wire:model="solucoes"></textarea>
                    </div>
                    <button class="btn btn-primary w-100">Cadastrar</button>
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            @forelse ($fornecedores as $f)
                <div class="card shadow-sm mb-3" wire:key="fornecedor-{{ $f['id'] }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $f['nome'] }}</div>
                                <div class="small text-muted">{{ $f['razao_social'] }} · CNPJ {{ $f['cnpj'] ?? '—' }}</div>
                            </div>
                            {!! $f['ativo'] ? '<span class="badge text-bg-success align-self-start">Ativo</span>' : '<span class="badge text-bg-secondary align-self-start">Inativo</span>' !!}
                        </div>
                        <div class="mt-2">
                            @forelse ($f['solucoes'] as $s)
                                <span class="badge rounded-pill text-bg-light border me-1">{{ $s['nome'] }}</span>
                            @empty
                                <span class="small text-muted">Sem soluções no catálogo.</span>
                            @endforelse
                        </div>
                        <form class="input-group input-group-sm mt-2" style="max-width: 380px" wire:submit="adicionarSolucao('{{ $f['id'] }}')">
                            <input class="form-control" placeholder="Nova solução" wire:model="novaSolucao.{{ $f['id'] }}">
                            <button class="btn btn-outline-secondary">Adicionar</button>
                        </form>
                        @error("solucao.{$f['id']}") <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">Nenhum fornecedor cadastrado.</div>
            @endforelse
        </div>
    </div>
</div>
