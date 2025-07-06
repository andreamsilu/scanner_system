<x-app-layout>
    <x-slot:title>
        Edit Card Template
    </x-slot>

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-edit me-2"></i>Edit Card Template
                </h1>
                <p class="text-muted mb-0">Update your card template design</p>
            </div>
            <a href="{{ route('card-templates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Templates
            </a>
        </div>

        <form action="{{ route('card-templates.update', $cardTemplate) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <!-- Form Section -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-edit me-2"></i>Template Details
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Basic Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-medium">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $cardTemplate->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label fw-medium">Template Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" 
                                            name="type" 
                                            required>
                                        <option value="">Select Type</option>
                                        @foreach($types as $key => $value)
                                            <option value="{{ $key }}" {{ old('type', $cardTemplate->type) == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-medium">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3" 
                                          placeholder="Describe your template...">{{ old('description', $cardTemplate->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Template Content -->
                            <div class="mb-4">
                                <label for="content" class="form-label fw-medium">Template Content <span class="text-danger">*</span></label>
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Available Variables:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Event Variables:</strong>
                                            <ul class="mb-0">
                                                <li><code>{{ event_name }}</code> - Event name</li>
                                                <li><code>{{ event_date }}</code> - Event date</li>
                                                <li><code>{{ event_time }}</code> - Event time</li>
                                                <li><code>{{ event_venue }}</code> - Event venue</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Attendee Variables:</strong>
                                            <ul class="mb-0">
                                                <li><code>{{ attendee_name }}</code> - Attendee name</li>
                                                <li><code>{{ attendee_email }}</code> - Attendee email</li>
                                                <li><code>{{ attendee_phone }}</code> - Attendee phone</li>
                                                <li><code>{{ attendee_company }}</code> - Attendee company</li>
                                                <li><code>{{ attendee_position }}</code> - Attendee position</li>
                                                <li><code>{{ attendee_status }}</code> - Registration status</li>
                                                <li><code>{{ ticket_id }}</code> - Ticket/QR token</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          id="content" 
                                          name="content" 
                                          rows="20" 
                                          required>{{ old('content', $cardTemplate->content) }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Preview Image -->
                            <div class="mb-4">
                                <label for="preview_image" class="form-label fw-medium">Preview Image</label>
                                @if($cardTemplate->preview_image)
                                    <div class="mb-3">
                                        <img src="{{ Storage::url($cardTemplate->preview_image) }}" 
                                             alt="Current preview" 
                                             class="img-thumbnail" 
                                             style="max-height: 200px;">
                                        <p class="text-muted small mt-1">Current preview image</p>
                                    </div>
                                @endif
                                <input type="file" 
                                       class="form-control @error('preview_image') is-invalid @enderror" 
                                       id="preview_image" 
                                       name="preview_image" 
                                       accept="image/*">
                                <div class="form-text">Upload a new preview image to replace the current one (optional)</div>
                                @error('preview_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', $cardTemplate->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="is_active">
                                        Active Template
                                    </label>
                                </div>
                                <div class="form-text">Active templates can be used for generating cards</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-eye me-2"></i>Live Preview
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="refreshPreview">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Preview
                                </button>
                            </div>
                            <div id="previewContainer" class="border rounded p-3 bg-light" style="min-height: 400px;">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-eye fa-2x mb-3"></i>
                                    <p class="mb-0">Preview will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('card-templates.show', $cardTemplate) }}" class="btn btn-outline-info">
                                        <i class="fas fa-eye me-2"></i>View Template
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('card-templates.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            let previewTimeout;

            // Function to update preview
            function updatePreview() {
                const content = $('#content').val();
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

            // Update preview on content change (with debounce)
            $('#content').on('input', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updatePreview, 1000);
            });

            // Manual refresh button
            $('#refreshPreview').on('click', updatePreview);

            // Initial preview
            updatePreview();
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .sticky-top {
            z-index: 1020;
        }
        #previewContainer {
            max-height: 600px;
            overflow-y: auto;
        }
        #previewContainer img {
            max-width: 100%;
            height: auto;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.875em;
        }
    </style>
    @endpush
</x-app-layout> 