// Common DataTables configuration
const dataTablesConfig = {
    responsive: true,
    processing: true,
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>Bfrtip',
    buttons: [
        {
            extend: 'collection',
            text: 'Export',
            className: 'btn btn-primary',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: ':not(:last-child)' // Exclude actions column
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'btn btn-danger',
                    exportOptions: {
                        columns: ':not(:last-child)' // Exclude actions column
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-info',
                    exportOptions: {
                        columns: ':not(:last-child)' // Exclude actions column
                    }
                }
            ]
        }
    ],
    language: {
        search: "Search:",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        infoFiltered: "(filtered from _MAX_ total entries)",
        zeroRecords: "No matching records found",
        paginate: {
            first: "First",
            last: "Last",
            next: "Next",
            previous: "Previous"
        },
        processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span>'
    },
    columnDefs: [
        {
            targets: -1, // Last column (actions)
            orderable: false,
            searchable: false
        }
    ]
};

// Function to initialize DataTable with custom options
function initDataTable(tableId, customOptions = {}) {
    return $(tableId).DataTable({
        ...dataTablesConfig,
        ...customOptions
    });
} 