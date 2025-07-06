<x-app-layout>
    <x-slot:title>
        {{ $cardTemplate->name }} - Card Template
    </x-slot>

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-id-card me-2"></i>{{ $cardTemplate->name }}
                </h1>
                <p class="text-muted mb-0">Card Template Details</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('card-templates.edit', $cardTemplate) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Template
                </a>
                <a href="{{ route('card-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Templates
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Template Details -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-info-circle me-2"></i>Template Information
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Name</label>
                            <p class="mb-0 fw-medium">{{ $cardTemplate->name }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Type</label>
                            <p class="mb-0">
                                <span class="badge bg-primary">
                                    {{ App\Models\CardTemplate::getTypes()[$cardTemplate->type] ?? $cardTemplate->type }}
                                </span>
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $cardTemplate->is_active ? 'success' : 'secondary' }}">
                                    {{ $cardTemplate->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>

                        @if($cardTemplate->description)
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Description</label>
                            <p class="mb-0">{{ $cardTemplate->description }}</p>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Created</label>
                            <p class="mb-0">{{ $cardTemplate->created_at->format('F j, Y g:i A') }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Last Updated</label>
                            <p class="mb-0">{{ $cardTemplate->updated_at->format('F j, Y g:i A') }}</p>
                        </div>

                        @if($cardTemplate->creator)
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Created By</label>
                            <p class="mb-0">{{ $cardTemplate->creator->name }}</p>
                        </div>
                        @endif

                        @if($cardTemplate->updater)
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted">Last Updated By</label>
                            <p class="mb-0">{{ $cardTemplate->updater->name }}</p>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="border-top pt-3">
                            <div class="d-grid gap-2">
                                <a href="{{ route('card-templates.edit', $cardTemplate) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Template
                                </a>
                                
                                <form action="{{ route('card-templates.toggle-status', $cardTemplate) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-{{ $cardTemplate->is_active ? 'warning' : 'success' }} w-100">
                                        <i class="fas fa-{{ $cardTemplate->is_active ? 'pause' : 'play' }} me-2"></i>
                                        {{ $cardTemplate->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                <form action="{{ route('card-templates.destroy', $cardTemplate) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Template
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Image -->
                @if($cardTemplate->preview_image)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-image me-2"></i>Preview Image
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <img src="{{ Storage::url($cardTemplate->preview_image) }}" 
                             alt="Template preview" 
                             class="img-fluid rounded">
                    </div>
                </div>
                @endif
            </div>

            <!-- Template Preview -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-eye me-2"></i>Template Preview
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="refreshPreview">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Preview
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="previewContainer" class="border rounded p-3 bg-light">
                            <!-- Preview content will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Template Code -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-code me-2"></i>Template Code
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="copyCode">
                                <i class="fas fa-copy me-2"></i>Copy Code
                            </button>
                        </div>
                        <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code id="templateCode">{{ $cardTemplate->content }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Function to update preview
            function updatePreview() {
                const content = $('#templateCode').text();
                const sampleData = {
                    event_name: 'Sample Event 2025',
                    event_date: 'January 15, 2025',
                    event_time: '2:00 PM',
                    event_venue: 'Sample Conference Center',
                    attendee_name: 'John Doe',
                    attendee_email: 'john.doe@example.com',
                    attendee_phone: '+255 712 345 678',
                    attendee_company: 'Sample Company Ltd',
                    attendee_position: 'Senior Manager',
                    attendee_status: 'Registered',
                    ticket_id: 'TKT-2025-001'
                };

                // Replace variables in content
                let previewContent = content;
                Object.keys(sampleData).forEach(key => {
                    const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
                    previewContent = previewContent.replace(regex, sampleData[key]);
                });

                $('#previewContainer').html(previewContent);
            }

            // Refresh preview button
            $('#refreshPreview').on('click', updatePreview);

            // Copy code button
            $('#copyCode').on('click', function() {
                const code = $('#templateCode').text();
                navigator.clipboard.writeText(code).then(() => {
                    const btn = $(this);
                    const originalText = btn.html();
                    btn.html('<i class="fas fa-check me-2"></i>Copied!');
                    setTimeout(() => {
                        btn.html(originalText);
                    }, 2000);
                });
            });

            // Initial preview
            updatePreview();
        });
    </script>
    @endpush

    @push('styles')
    <style>
        pre {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        pre code {
            background: none;
            padding: 0;
        }
        #previewContainer {
            min-height: 400px;
        }
        #previewContainer img {
            max-width: 100%;
            height: auto;
        }
        .badge {
            font-size: 0.875rem;
        }
    </style>
    @endpush
</x-app-layout> 