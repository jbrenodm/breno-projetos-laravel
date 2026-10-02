<div>
    <form class="card shadow-sm" wire:submit="salvar">
        <div class="card-body p-4">
            <h1 class="h5 mb-1">Crie a sua senha</h1>
            <p class="small text-muted">Olá, {{ auth()->user()->name }}. Você entrou com uma senha temporária; crie a sua para continuar.</p>
            @include('livewire.conta._regras-senha')
            @include('livewire.conta._form-senha', ['rotuloAtual' => 'Senha temporária'])
            <button class="btn btn-primary w-100" wire:loading.attr="disabled">Salvar e continuar</button>
        </div>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button class="btn btn-link btn-sm">Sair</button>
    </form>
</div>
