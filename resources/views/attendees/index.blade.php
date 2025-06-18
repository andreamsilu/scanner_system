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
                <a href="{{ route('events.attendees.template') }}" class="btn btn-success">
                    <i class="bi bi-file-excel me-2"></i>Download Template
                </a>
                <a href="{{ route('events.attendees.create', $event) }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Add Attendee
                </a>
                @role('admin')
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
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
                                <th>Ticket Type</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
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

        /* Loading indicator */
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.25rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            padding: 1rem !important;
            text-align: center !important;
            z-index: 1000 !important;
        }
    </style>
    @endpush

    <!-- Bulk Upload Modal -->
    <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
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
                                <li><strong>ticket_type</strong> (required) - Type of ticket (regular, vip, single, or double)</li>
                            </ul>
                            <p class="text-muted small mb-0">The first row should contain the column headers exactly as shown above.</p>
                        </div>
                    </div>

                    <form action="{{ route('events.attendees.bulk-upload.store', $event) }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">Upload File</label>
                            <input type="file" 
                                   name="csv_file" 
                                   id="csv_file" 
                                   accept=".xlsx,.xls,.csv" 
                                   required
                                   class="form-control @error('csv_file') is-invalid @enderror">
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Accepted formats: Excel (.xlsx, .xls) or CSV (.csv)</small>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading mb-2">Important Notes:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Only Excel (.xlsx, .xls) and CSV (.csv) files are accepted</li>
                                <li>Required columns: name, phone</li>
                                <li>Optional columns: email, position</li>
                                <li>Each attendee will automatically receive a unique token and QR code</li>
                                <li>Maximum file size is 10MB</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bulkUploadForm" class="btn btn-primary" id="uploadSubmitBtn">
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
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('events.attendees.index', $event) }}",
                    type: 'GET',
                    data: function(d) {
                        // Add custom filters
                        d.event_filter = $('#event_filter').val();
                        d.status = $('#status').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error, thrown);
                        toastr.error('Error loading attendees data. Please try again.');
                    }
                },
                columns: [
                    { 
                        data: 'name',
                        name: 'name'
                    },
                    { 
                        data: 'phone',
                        name: 'phone'
                    },
                    { 
                        data: 'token',
                        name: 'token'
                    },
                    { 
                        data: 'registration_status',
                        name: 'registration_status',
                        render: function(data) {
                            return data ? `<span class="badge bg-${data.badge}">${data.status}</span>` : '-';
                        }
                    },
                    { 
                        data: 'ticket_status',
                        name: 'ticket_status',
                        render: function(data) {
                            return data ? `<span class="badge bg-${data.badge}">${data.status}</span>` : '-';
                        }
                    },
                    { 
                        data: 'ticket_type',
                        name: 'ticket_type',
                        render: function(data) {
                            return data ? `<span class="badge bg-${data.badge}">${data.type}</span>` : '-';
                        }
                    },
                    { 
                        data: 'created_at',
                        name: 'created_at'
                    },
                    { 
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            if (!data) return '-';
                            return `
                                <div class="btn-group">
                                    <a href="${data.view_url}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="${data.edit_url}" 
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Edit Attendee">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="${data.delete_url}" 
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
                            `;
                        }
                    }
                ],
                pageLength: 15,
                lengthMenu: [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                order: [[6, 'desc']], // Sort by registration date by default
                dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                pagingType: 'full_numbers',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-2"></i>Export',
                        className: 'btn btn-sm btn-primary',
                        buttons: [
                            {
                                extend: 'excel',
                                text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel',
                                className: 'btn btn-sm btn-success',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5, 6]
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5, 6]
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
                                extend: 'print',
                                text: '<i class="bi bi-printer me-2"></i>Print',
                                className: 'btn btn-sm btn-info',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5, 6]
                                },
                                title: '{{ $event->name }} - Attendees List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            }
                        ]
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
                        last: '<i class="bi bi-chevron-double-right"></i>',
                        page: '<span class="mx-1">_PAGE_</span>'
                    },
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading attendees...'
                },
                drawCallback: function() {
                    // Reinitialize any tooltips or other plugins if needed
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Ensure buttons are properly initialized
            table.buttons().container().addClass('float-start');

            // Handle filter form submission
            $('form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Handle filter changes
            $('#event_filter, #status').on('change', function() {
                table.ajax.reload();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bulkUploadForm');
            const submitBtn = document.getElementById('uploadSubmitBtn');
            const modalBody = document.querySelector('#bulkUploadModal .modal-body');
            const modal = document.getElementById('bulkUploadModal');

            if (!form || !submitBtn || !modalBody || !modal) {
                console.error('Required elements not found');
                return;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                
                // Disable submit button and show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

                // Upload the file
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        modalBody.innerHTML = `
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">File Uploaded Successfully</h5>
                                <p class="text-muted">Your file has been uploaded and is now being processed in the background.</p>
                                <p class="text-muted">You will be redirected to the status page in a moment...</p>
                            </div>
                        `;

                        // Close modal and redirect after a short delay
                        setTimeout(() => {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) {
                                bsModal.hide();
                            }
                            window.location.href = data.redirect;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Upload failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            ${error.message || 'An error occurred during upload. Please try again.'}
                        </div>
                    `;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Upload and Process';
                });
            });
        });
    </script>
    @endpush
</x-app-layout> 