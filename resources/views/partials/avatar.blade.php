@php
    $tam = $tam ?? 38;
@endphp

@if ($user->foto)
    <img class="avatar"
         src="{{ asset($user->foto) }}?v={{ $user->updated_at->timestamp }}"
         alt="Foto de {{ $user->name }}"
         style="width: {{ $tam }}px; height: {{ $tam }}px;">
@else
    <span class="avatar inicial"
          style="width: {{ $tam }}px; height: {{ $tam }}px; font-size: {{ round($tam * 0.4) }}px;">
        {{ $user->iniciales }}
    </span>
@endif