@php($editando = $editandoId !== null)
<div>
    <h1 class="h3 mb-3">Fornecedores e soluções</h1>

    @if (session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form @class(['card shadow-sm', 'border-primary' => $editando]) wire:submit="salvar">
                <div @class(['card-header fw-semibold', 'bg-primary-subtle' => $editando])>{{ $editando ? 'Editar fornecedor' : 'Novo fornecedor' }}</div>
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
                    @unless ($editando)
                        <div class="mb-3">
                            <label class="form-label">Soluções <small class="text-muted">(opcional, uma por linha)</small></label>
                            <textarea class="form-control" rows="3" wire:model="solucoes"></textarea>
                        </div>
                    @else
                        <p class="small text-muted">As soluções são editadas no cartão do fornecedor.</p>
                    @endunless
                    <button class="btn btn-primary w-100">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    @if ($editando)
                        <button type="button" class="btn btn-light w-100 mt-2" wire:click="cancelarEdicao">Cancelar</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            @forelse ($fornecedores as $f)
                <div @class(['card shadow-sm mb-3', 'border-primary' => $editandoId === $f['id']]) wire:key="fornecedor-{{ $f['id'] }}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div @class(['text-body-secondary' => ! $f['ativo']])>
                                <div class="fw-semibold">{{ $f['nome'] }}
                                    {!! $f['ativo'] ? '<span class="badge text-bg-success ms-1">Ativo</span>' : '<span class="badge text-bg-secondary ms-1">Inativo</span>' !!}
                                </div>
                                <div class="small text-muted">{{ $f['razao_social'] }} · CNPJ {{ $f['cnpj'] ?? '—' }}</div>
                            </div>
                            <div class="text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $f['id'] }}')"><i class="bi bi-pencil"></i> Editar</button>
                                @if ($f['ativo'])
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="alterarSituacao('{{ $f['id'] }}', false)"
                                            wire:confirm="Inativar este fornecedor? Ele deixa de aparecer para novos projetos; os projetos existentes não mudam.">Inativar</button>
                                @else
                                    <button class="btn btn-sm btn-outline-success" wire:click="alterarSituacao('{{ $f['id'] }}', true)">Reativar</button>
                                @endif
                            </div>
                        </div>

                        <ul class="list-group list-group-flush mt-2">
                            @forelse ($f['solucoes'] as $s)
                                <li class="list-group-item px-0 py-2" wire:key="solucao-{{ $s['id'] }}">
                                    @if ($solucaoEditandoId === $s['id'])
                                        <form class="row g-2 align-items-start" wire:submit="salvarSolucao">
                                            <div class="col-md-4">
                                                <input class="form-control form-control-sm @error('solucaoNome') is-invalid @enderror" wire:model="solucaoNome" aria-label="Nome da solução">
                                                @error('solucaoNome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-5">
                                                <input class="form-control form-control-sm" wire:model="solucaoDescricao" placeholder="Descrição (opcional)" aria-label="Descrição">
                                            </div>
                                            <div class="col-md-3 text-nowrap">
                                                <button class="btn btn-sm btn-primary">Salvar</button>
                                                <button type="button" class="btn btn-sm btn-light" wire:click="cancelarEdicaoDeSolucao">Cancelar</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div @class(['text-body-secondary' => ! $s['ativo']])>
                                                <span @class(['text-decoration-line-through' => ! $s['ativo']])>{{ $s['nome'] }}</span>
                                                @unless ($s['ativo']) <span class="badge text-bg-secondary ms-1">Inativa</span> @endunless
                                                @if ($s['descricao']) <div class="small text-muted">{{ $s['descricao'] }}</div> @endif
                                            </div>
                                            <div class="text-nowrap">
                                                <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" wire:click="editarSolucao('{{ $f['id'] }}', '{{ $s['id'] }}')"><i class="bi bi-pencil"></i> Editar</button>
                                                @if ($s['ativo'])
                                                    <button class="btn btn-sm btn-link text-decoration-none text-secondary p-0" wire:click="alterarSituacaoSolucao('{{ $f['id'] }}', '{{ $s['id'] }}', false)"
                                                            wire:confirm="Inativar esta solução? Ela deixa de aparecer para novos projetos; os projetos existentes não mudam.">Inativar</button>
                                                @else
                                                    <button class="btn btn-sm btn-link text-decoration-none text-success p-0" wire:click="alterarSituacaoSolucao('{{ $f['id'] }}', '{{ $s['id'] }}', true)">Reativar</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li class="list-group-item px-0 small text-muted">Sem soluções no catálogo.</li>
                            @endforelse
                        </ul>
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
