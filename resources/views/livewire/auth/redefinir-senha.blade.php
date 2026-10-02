<div>
    <form class="card shadow-sm" wire:submit="salvar">
        <div class="card-body p-4">
            <h1 class="h5 mb-3">Criar nova senha</h1>
            @include('livewire.conta._regras-senha')

            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" autocomplete="username" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha">Nova senha</label>
                <input id="senha" type="password" class="form-control @error('senha') is-invalid @enderror" wire:model="senha" autocomplete="new-password" required>
                @error('senha') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha_confirmation">Confirme a nova senha</label>
                <input id="senha_confirmation" type="password" class="form-control" wire:model="senha_confirmation" autocomplete="new-password" required>
            </div>
            <button class="btn btn-primary w-100" wire:loading.attr="disabled">Salvar nova senha</button>
            <div class="text-center mt-3"><a href="{{ route('login') }}" class="small" wire:navigate>Voltar para o login</a></div>
        </div>
    </form>
</div>
