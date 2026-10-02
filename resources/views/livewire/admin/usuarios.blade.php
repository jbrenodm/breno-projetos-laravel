@inject('papeis', \App\Support\NomesDePapeis::class)
@php
    use Src\Identidade\Domain\Papel;
    $editando = $editandoId !== null;
@endphp
<div>
    <h1 class="h3 mb-3">Usuários</h1>

    @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif
    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="row g-4">
        <div class="col-lg-4">
            <form @class(['card shadow-sm', 'border-primary' => $editando]) wire:submit="salvar">
                <div @class(['card-header fw-semibold', 'bg-primary-subtle' => $editando])>{{ $editando ? 'Editar usuário' : 'Novo usuário' }}</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input class="form-control @error('nome') is-invalid @enderror" wire:model="nome" autocomplete="off">
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" autocomplete="off">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-label">Papéis <span class="text-danger">*</span></div>
                        @foreach ($todosPapeis as $p)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $p->value }}" id="papel-{{ $p->value }}" wire:model="papeis">
                                <label class="form-check-label" for="papel-{{ $p->value }}">{{ $papeis->nome($p) }}</label>
                            </div>
                        @endforeach
                        @error('papeis') <div class="small text-danger">{{ $message }}</div> @enderror
                    </div>
                    @unless ($editando)
                        <div class="mb-3">
                            <label class="form-label">Senha temporária <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input class="form-control @error('senhaTemporaria') is-invalid @enderror" wire:model="senhaTemporaria" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" wire:click="gerarSenha('senhaTemporaria')" title="Gerar senha"><i class="bi bi-shuffle"></i></button>
                                @error('senhaTemporaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-text">Mínimo 8 caracteres, com letras e números. O usuário troca no primeiro acesso.</div>
                        </div>
                    @endunless
                    <button class="btn btn-primary w-100">{{ $editando ? 'Salvar alterações' : 'Cadastrar' }}</button>
                    @if ($editando)
                        <button type="button" class="btn btn-light w-100 mt-2" wire:click="cancelarEdicao">Cancelar</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="list-group list-group-flush">
                    @foreach ($usuarios as $u)
                        <div @class(['list-group-item', 'list-group-item-primary' => $editandoId === $u['id']]) wire:key="usuario-{{ $u['id'] }}">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div @class(['text-body-secondary' => ! $u['ativo']])>
                                    <div class="fw-semibold">{{ $u['nome'] }}
                                        @if ($u['id'] === auth()->id()) <span class="badge text-bg-light border">você</span> @endif
                                        {!! $u['ativo'] ? '<span class="badge text-bg-success ms-1">Ativo</span>' : '<span class="badge text-bg-secondary ms-1">Inativo</span>' !!}
                                        @if ($u['deve_trocar_senha']) <span class="badge text-bg-warning ms-1" title="Ainda não trocou a senha temporária">Senha temporária</span> @endif
                                    </div>
                                    <div class="small text-muted">{{ $u['email'] }}</div>
                                    <div class="mt-1">
                                        @foreach ($u['papeis'] as $papel)
                                            <span class="badge rounded-pill text-bg-light border">{{ $papeis->nome(Papel::from($papel)) }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-nowrap align-self-start">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $u['id'] }}')"><i class="bi bi-pencil"></i> Editar</button>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="prepararRedefinicaoDeSenha('{{ $u['id'] }}')"><i class="bi bi-key"></i> Senha</button>
                                    @if ($u['ativo'])
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="alterarSituacao('{{ $u['id'] }}', false)"
                                                wire:confirm="Inativar {{ $u['nome'] }}? A pessoa é desconectada e os tokens de API dela são revogados.">Inativar</button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success" wire:click="alterarSituacao('{{ $u['id'] }}', true)">Reativar</button>
                                    @endif
                                </div>
                            </div>
                            @if ($redefinindoSenhaDe === $u['id'])
                                <form class="row g-2 align-items-start mt-2 p-2 bg-body-tertiary rounded" wire:submit="redefinirSenha">
                                    <div class="col-md-7">
                                        <div class="input-group input-group-sm">
                                            <input class="form-control @error('novaSenhaTemporaria') is-invalid @enderror" wire:model="novaSenhaTemporaria"
                                                   placeholder="Nova senha temporária" autocomplete="new-password" aria-label="Nova senha temporária">
                                            <button type="button" class="btn btn-outline-secondary" wire:click="gerarSenha('novaSenhaTemporaria')" title="Gerar senha"><i class="bi bi-shuffle"></i></button>
                                            @error('novaSenhaTemporaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-nowrap">
                                        <button class="btn btn-sm btn-primary">Definir</button>
                                        <button type="button" class="btn btn-sm btn-light" wire:click="cancelarRedefinicaoDeSenha">Cancelar</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
