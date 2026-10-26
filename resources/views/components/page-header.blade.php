@props(['title', 'breadcrumbs' => [], 'subtitle' => null])
<div class="mb-6">
    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs ui-muted mb-4">
        <a href="{{ route('dashboard') }}" class="text-inherit no-underline hover:text-teal-700">Workspace</a>
        @foreach($breadcrumbs as $label => $route)
            <span aria-hidden="true">/</span>
            @if($route)
                <a href="{{ $route }}" class="text-inherit no-underline hover:text-teal-700">{{ $label }}</a>
            @else
                <span aria-current="page">{{ $label }}</span>
            @endif
        @endforeach
    </nav>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="ui-heading mb-1">{{ $title }}</h1>
            @if($subtitle)<p class="ui-muted text-sm mb-0">{{ $subtitle }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">{{ $actions ?? '' }}</div>
    </div>
</div>
