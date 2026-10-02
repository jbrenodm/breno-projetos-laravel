<div>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h5 mb-3">Esqueci minha senha</h1>

            @if ($enviado)
                <div class="alert alert-success mb-3">
                    Se o e-mail estiver cadastrado e ativo, você receberá um link para redefinir a senha.
                    O link vale por {{ config('auth.passwords.users.expire') }} minutos.
                </div>
            @else
                <p class="small text-muted">Informe o seu e-mail. Enviaremos um link para você criar uma nova senha.</p>
                <form wire:submit="enviar">
                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email"
                               autocomplete="username" autofocus required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary w-100" wire:loading.attr="disabled">Enviar link</button>
                </form>
            @endif

            <div class="text-center mt-3"><a href="{{ route('login') }}" class="small" wire:navigate>Voltar para o login</a></div>
        </div>
    </div>
</div>
