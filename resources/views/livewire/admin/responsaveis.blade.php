@inject('papeis', \App\Support\NomesDePapeis::class)
@php
    use Src\Identidade\Domain\Papel;
    $editando = $editandoId !== null;
    $nomeFuncao = fn (string $funcao) => $papeis->nome(Papel::from($funcao)); // RN-41: nome atual da função
@endphp
<div>
    <h1 class="h3 mb-1">Responsáveis</h1>
    <p class="text-muted mb-3">Pessoas que executam as atividades como {{ $papeis->sigla(Papel::ACCOUNT_MANAGER) }} e/ou {{ $papeis->sigla(Papel::PRE_VENDAS) }}. Não entram no sistema.</p>

    @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <form @class(['card shadow-sm', 'border-primary' => $editando]) wire:submit="salvar">
                <div @class(['card-header fw-semibold', 'bg-primary-subtle' => $editando])>{{ $editando ? 'Editar responsável' : 'Novo responsável' }}</div>
                <div class="card-body">
                    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input class="form-control @error('nome') is-invalid @enderror" wire:model="nome" autocomplete="off">
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" autocomplete="off">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-label">Funções <span class="text-danger">*</span></div>
                        @foreach ($todasFuncoes as $f)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $f->value }}" id="funcao-{{ $f->value }}" wire:model="funcoes">
                                <label class="form-check-label" for="funcao-{{ $f->value }}">{{ $nomeFuncao($f->value) }}</label>
                            </div>
                        @endforeach
                        @error('funcoes') <div class="small text-danger">{{ $message }}</div> @enderror
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
                        <thead class="table-light"><tr><th>Nome</th><th>E-mail</th><th>Funções</th><th>Situação</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($responsaveis as $r)
                                <tr wire:key="responsavel-{{ $r['id'] }}" @class(['table-primary' => $editandoId === $r['id'], 'text-body-secondary' => ! $r['ativo']])>
                                    <td class="fw-semibold">{{ $r['nome'] }}</td>
                                    <td>{{ $r['email'] ?? '—' }}</td>
                                    <td>
                                        @foreach ($r['funcoes'] as $funcao)
                                            <span class="badge rounded-pill text-bg-light border">{{ $nomeFuncao($funcao) }}</span>
                                        @endforeach
                                    </td>
                                    <td>{!! $r['ativo'] ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' !!}</td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $r['id'] }}')" title="Editar"><i class="bi bi-pencil"></i> Editar</button>
                                        @if ($r['ativo'])
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="alterarSituacao('{{ $r['id'] }}', false)"
                                                    wire:confirm="Inativar {{ $r['nome'] }}? Deixa de aparecer para novas atividades; as atividades existentes não mudam.">Inativar</button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" wire:click="alterarSituacao('{{ $r['id'] }}', true)">Reativar</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum responsável cadastrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
