<div>
    <form class="card shadow-sm" wire:submit="entrar">
        <div class="card-body p-4">
            <h1 class="h5 mb-3">Entrar</h1>

            @if (session('erro')) <div class="alert alert-warning">{{ session('erro') }}</div> @endif
            @if (session('sucesso')) <div class="alert alert-success">{{ session('sucesso') }}</div> @endif

            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email"
                       autocomplete="username" autofocus required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha">Senha</label>
                <input id="senha" type="password" class="form-control @error('senha') is-invalid @enderror" wire:model="senha"
                       autocomplete="current-password" required>
                @error('senha') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="lembrar" wire:model="lembrar">
                    <label class="form-check-label" for="lembrar">Lembrar de mim</label>
                </div>
                <a href="{{ route('password.request') }}" class="small" wire:navigate>Esqueci minha senha</a>
            </div>
            <button class="btn btn-primary w-100" wire:loading.attr="disabled">
                <span wire:loading wire:target="entrar" class="spinner-border spinner-border-sm me-1"></span>Entrar
            </button>
        </div>
    </form>
</div>
