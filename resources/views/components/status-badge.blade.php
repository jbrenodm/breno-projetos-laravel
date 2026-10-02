@props(['status'])
@php
    $cor = match ($status) {
        'Concluído', 'Concluída' => 'success',
        'Em Andamento' => 'primary',
        'Parado', 'Parada' => 'warning',
        'Cancelado' => 'danger',
        default => 'secondary',
    };
@endphp
<span {{ $attributes->class("badge text-bg-$cor") }}>{{ $status }}</span>
