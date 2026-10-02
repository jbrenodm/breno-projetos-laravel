<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Projetos' }} · breno-projetos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('projetos.index') }}" wire:navigate>
                <i class="bi bi-kanban me-2"></i>breno-projetos
            </a>
            <div class="navbar-nav">
                <a class="nav-link @if(request()->routeIs('projetos.*')) active @endif" href="{{ route('projetos.index') }}" wire:navigate>Projetos</a>
                <a class="nav-link @if(request()->routeIs('clientes.*')) active @endif" href="{{ route('clientes.index') }}" wire:navigate>Clientes</a>
                <a class="nav-link @if(request()->routeIs('fornecedores.*')) active @endif" href="{{ route('fornecedores.index') }}" wire:navigate>Fornecedores</a>
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        {{ $slot }}
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
