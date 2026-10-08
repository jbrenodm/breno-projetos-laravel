<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Projetos' }} · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('projetos.index') }}" wire:navigate>
                <i class="bi bi-kanban me-2"></i>{{ config('app.name') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                    aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="menuPrincipal">
                <div class="navbar-nav">
                    <a class="nav-link @if(request()->routeIs('projetos.*')) active @endif" href="{{ route('projetos.index') }}" wire:navigate>Projetos</a>
                    <a class="nav-link @if(request()->routeIs('clientes.*')) active @endif" href="{{ route('clientes.index') }}" wire:navigate>Clientes</a>
                    <a class="nav-link @if(request()->routeIs('fornecedores.*')) active @endif" href="{{ route('fornecedores.index') }}" wire:navigate>Fornecedores</a>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('dashboards.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dashboards</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item @if(request()->routeIs('dashboards.operacional')) active @endif" href="{{ route('dashboards.operacional') }}" wire:navigate>
                                <i class="bi bi-speedometer2 me-2"></i>Painel operacional</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('dashboards.prazos')) active @endif" href="{{ route('dashboards.prazos') }}" wire:navigate>
                                <i class="bi bi-calendar-check me-2"></i>Prazos e entrega</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('dashboards.atividades')) active @endif" href="{{ route('dashboards.atividades') }}" wire:navigate>
                                <i class="bi bi-list-check me-2"></i>Todas as atividades</a></li>
                        </ul>
                    </div>
                    @auth
                        <div class="nav-item dropdown ms-md-3">
                            <a class="nav-link dropdown-toggle @if(request()->routeIs('conta', 'usuarios.*', 'papeis.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item @if(request()->routeIs('conta')) active @endif" href="{{ route('conta') }}" wire:navigate>
                                    <i class="bi bi-person-gear me-2"></i>Minha conta</a></li>
                                @can('admin-geral')
                                    <li><a class="dropdown-item @if(request()->routeIs('usuarios.*')) active @endif" href="{{ route('usuarios.index') }}" wire:navigate>
                                        <i class="bi bi-people me-2"></i>Usuários</a></li>
                                    <li><a class="dropdown-item @if(request()->routeIs('responsaveis.*')) active @endif" href="{{ route('responsaveis.index') }}" wire:navigate>
                                        <i class="bi bi-person-workspace me-2"></i>Responsáveis</a></li>
                                    <li><a class="dropdown-item @if(request()->routeIs('tipos-atividade.*')) active @endif" href="{{ route('tipos-atividade.index') }}" wire:navigate>
                                        <i class="bi bi-tags me-2"></i>Tipos de atividade</a></li>
                                    <li><a class="dropdown-item @if(request()->routeIs('papeis.*')) active @endif" href="{{ route('papeis.index') }}" wire:navigate>
                                        <i class="bi bi-person-badge me-2"></i>Papéis</a></li>
                                @endcan
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        {{ $slot }}
    </main>

    {{-- data-navigate-once: com wire:navigate o Livewire reexecutaria o script a cada troca de página, duplicando os
         ouvintes de clique do Bootstrap (dropdown e menu do celular abriam e fechavam no mesmo clique). --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" data-navigate-once></script>
    @livewireScripts
</body>
</html>
