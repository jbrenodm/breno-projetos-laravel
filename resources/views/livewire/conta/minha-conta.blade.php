<div>
    <h1 class="h3 mb-1">Minha conta</h1>
    <div class="text-muted mb-3">{{ auth()->user()->name }} · {{ auth()->user()->email }}</div>

    @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <form class="card shadow-sm" wire:submit="salvarSenha">
                <div class="card-header fw-semibold">Trocar senha</div>
                <div class="card-body">
                    @include('livewire.conta._regras-senha')
                    @include('livewire.conta._form-senha')
                    <button class="btn btn-primary w-100" wire:loading.attr="disabled">Salvar nova senha</button>
                </div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Tokens de API</div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Para integrações com a API (<code>/api/v1/...</code>). Envie o token no cabeçalho
                        <code>Authorization: Bearer &lt;token&gt;</code>. Trate-o como uma senha.
                    </p>

                    @if ($tokenGerado)
                        <div class="alert alert-warning">
                            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Copie agora: este token não será exibido de novo.</div>
                            <code class="d-block text-break user-select-all p-2 bg-body rounded border" data-token>{{ $tokenGerado }}</code>
                        </div>
                    @endif

                    <form class="input-group mb-3" wire:submit="gerarToken">
                        <input class="form-control @error('nomeDoToken') is-invalid @enderror" wire:model="nomeDoToken" placeholder="Nome (ex.: Integração CRM)" maxlength="100" aria-label="Nome do token">
                        <button class="btn btn-outline-primary">Gerar token</button>
                        @error('nomeDoToken') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </form>
                    @error('tokens') <div class="alert alert-danger">{{ $message }}</div> @enderror

                    <ul class="list-group">
                        @forelse ($tokens as $t)
                            <li class="list-group-item d-flex justify-content-between align-items-center" wire:key="token-{{ $t['id'] }}">
                                <div>
                                    <div class="fw-semibold">{{ $t['nome'] }}</div>
                                    <div class="small text-muted">Criado em {{ $t['criado_em'] }} · {{ $t['ultimo_uso'] ? 'último uso '.$t['ultimo_uso'] : 'nunca usado' }}</div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" wire:click="revogarToken('{{ $t['id'] }}')"
                                        wire:confirm="Revogar este token? Integrações que o usam deixarão de funcionar.">Revogar</button>
                            </li>
                        @empty
                            <li class="list-group-item text-muted small">Nenhum token criado.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
