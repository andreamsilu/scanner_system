<x-app-layout>
    <x-page-header 
        title="Tickets for {{ $event->name }}"
        :breadcrumbs="[
            'Events' => route('events.index'),
            $event->name => route('events.show', $event),
            'Tickets' => null
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Event
            </a>
        </x-slot>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container-fluid py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="typeFilter" class="form-select">
                            <option value="">All Types</option>
                            <option value="vip">VIP</option>
                            <option value="regular">Regular</option>
                            <option value="single">Single</option>
                            <option value="double">Double</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ticketsTable" class="table table-hover align-middle datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Attendee</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Last Scan</th>
                                <th>Created</th>
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
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.25rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            padding: 1rem !important;
            text-align: center !important;
            z-index: 1000 !important;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
    </style>
    @endpush

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
            if ($.fn.DataTable.isDataTable('#ticketsTable')) {
                $('#ticketsTable').DataTable().destroy();
            }

            var table = $('#ticketsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('events.events.tickets.index', ['event' => $event->id]) }}",
                    type: 'GET',
                    data: function(d) {
                        // Add custom filters
                        d.status = $('#statusFilter').val();
                        d.type = $('#typeFilter').val();
                    }
                },
                columns: [
                    {
                        data: 'attendee',
                        render: function(data) {
                            return `<div>
                                <div class="fw-bold">${data.name}</div>
                                <small class="text-muted">${data.token}</small>
                            </div>`;
                        }
                    },
                    {
                        data: 'type',
                        render: function(data) {
                            return `<span class="badge bg-${data.badge}">${data.type}</span>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            return `<span class="badge bg-${data.badge}">${data.status}</span>`;
                        }
                    },
                    {
                        data: 'last_scan',
                        render: function(data) {
                            if (!data) return '<span class="text-muted">Never</span>';
                            return `<div>
                                <div>${data.time}</div>
                                <small class="text-muted">${data.full_time}</small>
                            </div>`;
                        }
                    },
                    { data: 'created_at' },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            let html = `
                                <div class="btn-group">
                                    <a href="${data.view_url}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="${data.print_url}" 
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Print Ticket"
                                       target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="${data.export_url}" 
                                       class="btn btn-sm btn-outline-success"
                                       title="Export Ticket">
                                        <i class="bi bi-file-export"></i>
                                    </a>`;
                            
                            if (data.deactivate_url) {
                                html += `
                                    <form action="${data.deactivate_url}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to deactivate this ticket?')">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="Deactivate Ticket">
                                            <i class="bi bi-ban"></i>
                                        </button>
                                    </form>`;
                            }
                            
                            html += `</div>`;
                            return html;
                        }
                    }
                ],
                pageLength: 15,
                lengthMenu: [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                order: [[4, 'desc']], // Sort by created_at by default
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
                                    columns: [0, 1, 2, 3, 4]
                                },
                                title: '{{ $event->name }} - Tickets List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF',
                                className: 'btn btn-sm btn-danger',
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4]
                                },
                                title: '{{ $event->name }} - Tickets List',
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
                                    columns: [0, 1, 2, 3, 4]
                                },
                                title: '{{ $event->name }} - Tickets List',
                                messageTop: 'Generated on: ' + new Date().toLocaleDateString()
                            }
                        ]
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search tickets...",
                    emptyTable: "No tickets found.",
                    info: "Showing _START_ to _END_ of _TOTAL_ tickets",
                    infoEmpty: "Showing 0 to 0 of 0 tickets",
                    infoFiltered: "(filtered from _MAX_ total tickets)",
                    lengthMenu: "Show _MENU_ tickets per page",
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>',
                        page: '<span class="mx-1">_PAGE_</span>'
                    },
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Loading tickets...'
                },
                drawCallback: function() {
                    // Reinitialize any tooltips or other plugins if needed
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Ensure buttons are properly initialized
            table.buttons().container().addClass('float-start');

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Handle filter changes
            $('#statusFilter, #typeFilter').on('change', function() {
                table.ajax.reload();
            });

            // Handle form reset
            $('#filterForm').on('reset', function() {
                setTimeout(function() {
                    table.ajax.reload();
                }, 0);
            });
        });
    </script>
    @endpush
</x-app-layout> 