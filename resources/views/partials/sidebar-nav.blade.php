{{-- Menu latéral groupé. $menu : groupe => [[libellé, chemin, icône, motif actif]] --}}
<nav class="nav">
    @foreach ($menu as $group => $items)
        <div class="nav-group">{{ $group }}</div>
        @foreach ($items as [$label, $href, $icon, $pattern])
            <a href="{{ $href }}" class="{{ $isActive($pattern) ? 'active' : '' }}" @if ($isActive($pattern)) aria-current="page" @endif>
                <i class="{{ $icon }}"></i><span>{{ $label }}</span>
            </a>
        @endforeach
    @endforeach
</nav>
