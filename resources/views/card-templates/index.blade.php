<x-app-layout>
    <x-slot:title>
        Card Templates
    </x-slot>

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-id-card me-2"></i>Card Templates
                </h1>
                <p class="text-muted mb-0">Manage your event card templates</p>
            </div>
            <a href="{{ route('card-templates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Template
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="typeFilter">
                            <option value="">All Types</option>
                            @foreach(App\Models\CardTemplate::getTypes() as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search templates...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-outline-secondary w-100" id="clearFilters">
                            <i class="fas fa-times me-2"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="row" id="templatesGrid">
            @forelse($templates as $template)
                <div class="col-lg-4 col-md-6 mb-4 template-card" 
                     data-type="{{ $template->type }}" 
                     data-status="{{ $template->is_active ? '1' : '0' }}"
                     data-name="{{ strtolower($template->name) }}">
                    <div class="card h-100 shadow-sm border-0">
                        <!-- Preview Image -->
                        <div class="card-img-top bg-light" style="height: 200px; overflow: hidden;">
                            @if($template->preview_image)
                                <img src="{{ Storage::url($template->preview_image) }}" 
                                     class="w-100 h-100 object-fit-cover" 
                                     alt="{{ $template->name }}">
                            @else
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p class="mb-0">No Preview</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0">{{ $template->name }}</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('card-templates.show', $template) }}">
                                                <i class="fas fa-eye me-2"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('card-templates.edit', $template) }}">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item preview-template" 
                                               href="#" 
                                               data-template-id="{{ $template->id }}">
                                                <i class="fas fa-eye me-2"></i>Preview
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('card-templates.toggle-status', $template) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }} me-2"></i>
                                                    {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('card-templates.destroy', $template) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <p class="card-text text-muted small mb-2">
                                {{ \Illuminate\Support\Str::limit($template->description, 100) }}
                            </p>

                            <div class="mb-3">
                                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }} me-2">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="badge bg-primary">
                                    {{ App\Models\CardTemplate::getTypes()[$template->type] ?? $template->type }}
                                </span>
                            </div>

                            <div class="mt-auto">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Created by {{ $template->creator->name ?? 'Unknown' }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $template->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No templates found</h5>
                            <p class="text-muted mb-3">Create your first card template to get started.</p>
                            <a href="{{ route('card-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Template
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($templates->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Template Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Filter functionality
            function filterTemplates() {
                const type = $('#typeFilter').val();
                const status = $('#statusFilter').val();
                const search = $('#searchInput').val().toLowerCase();

                $('.template-card').each(function() {
                    const card = $(this);
                    const cardType = card.data('type');
                    const cardStatus = card.data('status');
                    const cardName = card.data('name');

                    let show = true;

                    if (type && cardType !== type) show = false;
                    if (status && cardStatus !== status) show = false;
                    if (search && !cardName.includes(search)) show = false;

                    card.toggle(show);
                });
            }

            $('#typeFilter, #statusFilter').on('change', filterTemplates);
            $('#searchInput').on('input', filterTemplates);

            $('#clearFilters').on('click', function() {
                $('#typeFilter, #statusFilter').val('');
                $('#searchInput').val('');
                filterTemplates();
            });

            // Preview functionality
            $('.preview-template').on('click', function(e) {
                e.preventDefault();
                const templateId = $(this).data('template-id');
                
                // Show loading
                $('#previewContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                $('#previewModal').modal('show');

                // Fetch preview
                $.get(`/card-templates/${templateId}/preview`)
                    .done(function(response) {
                        $('#previewContent').html(response.html);
                    })
                    .fail(function() {
                        $('#previewContent').html('<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><p>Failed to load preview</p></div>');
                    });
            });
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .template-card .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .template-card .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        .object-fit-cover {
            object-fit: cover;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.75rem;
        }
    </style>
    @endpush
</x-app-layout> 