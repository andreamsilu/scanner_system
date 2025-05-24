@props(['title', 'breadcrumbs' => [], 'subtitle' => null])

<!-- Breadcrumb with Welcome Message -->
<div class="bg-white py-2 px-3 border-bottom">
    <div class="d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                @foreach($breadcrumbs as $label => $route)
                    @if($route)
                        <li class="breadcrumb-item"><a href="{{ $route }}">{{ $label }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                    @endif
                @endforeach
                @if(empty($breadcrumbs))
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                @endif
            </ol>
        </nav>
        <div class="text-muted border-start ps-3 ms-3">
            Welcome, {{ Auth::user()->name }}
        </div>
    </div>
</div>

<!-- Header -->
<div class="bg-white border-bottom py-3 px-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 fw-bold text-dark mb-0">{{ $title }}</h2>
            @if(isset($subtitle))
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            {{ $actions ?? '' }}
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Breadcrumb styles */
    .breadcrumb {
        margin-bottom: 0;
        padding: 0.5rem 0;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: "›";
        font-size: 1.2rem;
        line-height: 1;
        vertical-align: middle;
    }
    
    .breadcrumb-item a {
        color: #6c757d;
        text-decoration: none;
    }
    
    .breadcrumb-item a:hover {
        color: #0d6efd;
    }
    
    .breadcrumb-item.active {
        color: #495057;
    }

    /* Header styles */
    .h4 {
        font-size: 1.25rem;
        font-weight: 600;
    }

    /* Action buttons spacing */
    .gap-2 {
        gap: 0.5rem !important;
    }

    /* Welcome message in breadcrumb */
    .text-muted.border-start {
        font-size: 0.875rem;
        border-color: #dee2e6 !important;
    }
</style>
@endpush 