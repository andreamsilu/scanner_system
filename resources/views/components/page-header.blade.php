@props(['title', 'breadcrumbs' => [], 'subtitle' => null])

<!-- Breadcrumb with Welcome Message -->
<div class="bg-white py-2 px-3 border-bottom">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-link">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    @foreach($breadcrumbs as $label => $route)
                        @if($route)
                            <li class="breadcrumb-item">
                                <a href="{{ $route }}" class="breadcrumb-link">{{ $label }}</a>
                            </li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">
                                <span class="breadcrumb-text">{{ $label }}</span>
                            </li>
                        @endif
                    @endforeach
                    @if(empty($breadcrumbs))
                        <li class="breadcrumb-item active" aria-current="page">
                            <span class="breadcrumb-text">{{ $title }}</span>
                        </li>
                    @endif
                </ol>
            </nav>
            <div class="text-muted border-start ps-3 ms-3">
                Welcome, {{ Auth::user()->name }}
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="bg-white border-bottom py-3 px-3">
    <div class="container-fluid">
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
        color: #6c757d;
    }
    
    /* Breadcrumb link styles */
    .breadcrumb-link {
        color: #6c757d;
        text-decoration: none;
        transition: color 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
    }
    
    .breadcrumb-link:hover {
        color: #0d6efd;
        text-decoration: underline;
    }
    
    .breadcrumb-link:focus {
        color: #0d6efd;
        outline: none;
        text-decoration: underline;
    }
    
    /* Active breadcrumb */
    .breadcrumb-item.active .breadcrumb-text {
        color: #495057;
        font-weight: 500;
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

    /* Breadcrumb hover effect */
    .breadcrumb-item:not(.active) {
        position: relative;
    }

    .breadcrumb-item:not(.active)::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #0d6efd;
        transform: scaleX(0);
        transition: transform 0.2s ease-in-out;
    }

    .breadcrumb-item:not(.active):hover::after {
        transform: scaleX(1);
    }
</style>
@endpush 