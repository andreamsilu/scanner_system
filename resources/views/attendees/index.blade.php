<x-app-layout>
    <x-page-header 
        title="Attendees"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Attendees' => null
        ]"
    >
        <x-slot name="actions">
            <div class="d-flex gap-2">
                <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Add Attendee
                </a>
                @role('admin')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                    <i class="bi bi-upload me-2"></i>Bulk Upload
                </button>
                @endrole
            </div>
        </x-slot>
    </x-page-header>

    <div class="container-fluid py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('events.attendees.index', $event) }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name or email...">
                    </div>
                    <div class="col-md-3">
                        <label for="event_filter" class="form-label">Event</label>
                        <select class="form-select" id="event_filter" name="event_filter">
                            <option value="">All Events</option>
                            @foreach($events as $eventOption)
                                <option value="{{ $eventOption->id }}" {{ request('event_filter') == $eventOption->id ? 'selected' : '' }}>
                                    {{ $eventOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="unchecked_in" {{ request('status') === 'unchecked_in' ? 'selected' : '' }}>Unchecked In</option>
                            <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <a href="{{ route('events.attendees.index', $event) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendees Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle datatable" id="attendeesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Token</th>
                                <th>Registration Status</th>
                                <th>Ticket Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendees as $attendee)
                                <tr>
                                    <td>{{ $attendee->name }}</td>
                                    <td>{{ $attendee->phone ?? 'N/A' }}</td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $attendee->token }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $attendee->registration_status === 'checked_in' ? 'success' : ($attendee->registration_status === 'cancelled' ? 'danger' : 'primary') }}">
                                            {{ $attendee->registration_status === 'unchecked_in' ? 'Unchecked In' : ucfirst($attendee->registration_status) }}
                                        </span>
                                        @if($attendee->registration_status === 'checked_in' && $attendee->checked_in_at)
                                            <br>
                                            <small class="text-muted">Checked in: {{ $attendee->checked_in_at->format('M j, Y g:i A') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendee->ticket)
                                            <span class="badge bg-{{ $attendee->ticket->status === 'used' ? 'success' : ($attendee->ticket->status === 'active' ? 'primary' : 'danger') }}">
                                                {{ ucfirst($attendee->ticket->status) }}
                                            </span>
                                            @if($attendee->ticket->used_at)
                                                <br>
                                                <small class="text-muted">Used: {{ $attendee->ticket->used_at->format('M j, Y g:i A') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">No Ticket</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $attendee->created_at->format('M j, Y g:i A') }}
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('events.attendees.show', ['event' => $event, 'attendee' => $attendee]) }}" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('events.attendees.edit', ['event' => $event, 'attendee' => $attendee]) }}" 
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Edit Attendee">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('events.attendees.destroy', ['event' => $event, 'attendee' => $attendee]) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this attendee? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete Attendee">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                                            <p class="mb-0">No attendees found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($attendees->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $attendees->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        .dt-buttons {
            margin-bottom: 1rem;
            float: left;
        }
        .dt-button {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            margin-right: 0.25rem;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        .dt-button:hover {
            background-color: #f8f9fa;
        }
        .dt-button-collection {
            width: auto !important;
            padding: 0.5rem;
        }
        .dt-button-collection .dt-button {
            margin: 0.25rem;
            width: calc(100% - 0.5rem);
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Processing overlay styles */
        #uploadProcessingOverlay {
            backdrop-filter: blur(5px);
            transition: opacity 0.3s ease-in-out;
        }
        
        #uploadProcessingOverlay .progress {
            height: 0.5rem;
        }
        
        #uploadProcessingOverlay .progress-bar {
            transition: width 0.5s ease-in-out;
        }
    </style>
    @endpush

    <!-- Bulk Upload Modal -->
    <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content position-relative">
                <!-- Processing Overlay -->
                <div id="uploadProcessingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-90 d-none" style="z-index: 1050;">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="text-primary mb-2">Processing Upload</h5>
                        <p class="text-muted mb-0" id="uploadStatus">Please wait while we process your file...</p>
                        <div class="progress mt-3" style="width: 80%;">
                            <div id="uploadProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUploadModalLabel">
                        <i class="bi bi-upload me-2"></i>Bulk Upload Attendees
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2">Instructions</h6>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2">Please prepare a CSV file with the following columns:</p>
                            <ul class="list-disc list-inside mb-3">
                                <li><strong>name</strong> (required) - Full name of the attendee</li>
                                <li><strong>phone</strong> (required) - Phone number</li>
                                <li><strong>email</strong> (optional) - Email address</li>
                                <li><strong>position</strong> (optional) - Job position or title</li>
                            </ul>
                            <p class="text-muted small mb-0">The first row should contain the column headers exactly as shown above.</p>
                        </div>
                    </div>

                    <form action="{{ route('events.attendees.bulk-upload.store', $event) }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" 
                                   name="csv_file" 
                                   id="csv_file" 
                                   accept=".csv,.txt" 
                                   required
                                   class="form-control @error('csv_file') is-invalid @enderror">
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading mb-2">Important Notes:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Each attendee will automatically receive a unique token and QR code</li>
                                <li>The process may take some time depending on the number of records</li>
                                <li>You will be notified when the upload is complete</li>
                                <li>Maximum file size is 10MB</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bulkUploadForm" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Upload and Process
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Destroy existing DataTable instance if it exists
            if ($.fn.DataTable.isDataTable('#attendeesTable')) {
                $('#attendeesTable').DataTable().destroy();
            }

            var table = $('#attendeesTable').DataTable({
                responsive: true,
                processing: true,
                serverSide: false, // Set to false since we're using blade rendering
                pageLength: 10,
                order: [[4, 'desc']], // Sort by registration date by default
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-2"></i>Export',
                        className: 'btn btn-primary',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                                className: 'btn btn-success',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'csv',
                                text: '<i class="bi bi-file-earmark-text me-2"></i>CSV',
                                className: 'btn btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString(),
                                customize: function(doc) {
                                    doc.defaultStyle.fontSize = 10;
                                    doc.styles.tableHeader.fontSize = 11;
                                    doc.styles.tableHeader.fillColor = '#f8f9fa';
                                    doc.styles.tableHeader.color = '#212529';
                                    doc.pageMargins = [40, 60, 40, 60];
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="bi bi-clipboard me-2"></i>Copy',
                                className: 'btn btn-secondary',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4] // Exclude actions column
                                },
                                title: '{{ $event->name }} - Attendees List'
                            }
                        ]
                    }
                ],
                columnDefs: [
                    { 
                        orderable: false, 
                        targets: [5], // Disable sorting on actions column
                        searchable: false
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search attendees...",
                    emptyTable: "No attendees found.",
                    info: "Showing _START_ to _END_ of _TOTAL_ attendees",
                    infoEmpty: "Showing 0 to 0 of 0 attendees",
                    infoFiltered: "(filtered from _MAX_ total attendees)",
                    lengthMenu: "Show _MENU_ attendees per page",
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                },
                drawCallback: function() {
                    // Reinitialize any tooltips or other plugins if needed
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Ensure buttons are properly initialized
            table.buttons().container().addClass('float-start');
        });

        // Handle form submission with enhanced loading state
        document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const overlay = document.getElementById('uploadProcessingOverlay');
            const uploadStatus = document.getElementById('uploadStatus');
            const uploadProgress = document.getElementById('uploadProgress');
            
            // Show processing overlay
            overlay.classList.remove('d-none');
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            // Simulate progress updates (you can remove this in production)
            let progress = 0;
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += 10;
                    uploadProgress.style.width = progress + '%';
                    if (progress === 30) uploadStatus.textContent = 'Validating file format...';
                    if (progress === 60) uploadStatus.textContent = 'Processing attendee records...';
                    if (progress === 90) uploadStatus.textContent = 'Finalizing upload...';
                }
            }, 1000);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                uploadProgress.style.width = '100%';
                uploadStatus.textContent = 'Upload complete!';
                
                // Close modal after a short delay
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkUploadModal'));
                    modal.hide();
                    
                    // Show success message
                    if (data.success) {
                        // Redirect to status page if provided
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            // Show success message and reload page
                            alert('Bulk upload completed successfully!');
                            window.location.reload();
                        }
                    } else {
                        // Show error message
                        alert(data.message || 'An error occurred during upload.');
                    }
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Error:', error);
                uploadStatus.textContent = 'Upload failed!';
                uploadProgress.classList.remove('progress-bar-striped', 'progress-bar-animated');
                uploadProgress.classList.add('bg-danger');
                alert('An error occurred during upload. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-upload me-2"></i>Upload and Process';
                
                // Hide overlay after a delay
                setTimeout(() => {
                    overlay.classList.add('d-none');
                    uploadProgress.style.width = '0%';
                    uploadProgress.classList.remove('bg-danger');
                    uploadProgress.classList.add('progress-bar-striped', 'progress-bar-animated');
                }, 2000);
            });
        });
    </script>
    @endpush
</x-app-layout> 