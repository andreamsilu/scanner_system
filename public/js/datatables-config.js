function initDataTable(selector, options = {}) {
    const defaultOptions = {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    {
                        extend: 'excel',
                        text: 'Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: ':not(:last-child)' // Export all columns except the last one (actions)
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        className: 'btn btn-danger',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        className: 'btn btn-info',
                        exportOptions: {
                            columns: ':not(:last-child)'
                        }
                    }
                ]
            }
        ],
        responsive: true,
        processing: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries available",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching records found",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    };

    // Merge default options with provided options
    const mergedOptions = { ...defaultOptions, ...options };
    
    // Initialize DataTable
    return $(selector).DataTable(mergedOptions);
} 