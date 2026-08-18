@props(['tabs' => []])

<nav {{ $attributes->merge(['class' => 'page-tabs']) }} aria-label="Page sections">
    @foreach ($tabs as $tab)
        <a class="page-tab {{ ! empty($tab['active']) ? 'active' : '' }}" href="{{ $tab['url'] }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
