<div class="col-xl-12 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
    <div class="s-b-inner s-b-notifications bg-white b-shadow-4 rounded">
        <div class="s-b-n-header" id="tabs">
            <h2 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                Email Template</h2>
        </div>
        <div class="s-b-n-content">
            <div class="table-responsive">
                <table class="table table-bordered" id="example">
                    <thead>
                        <tr>
                            <th>SL.</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table data will be injected here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<link href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {

        let table = $('#example').DataTable({
            ajax: {
                url: '{{ route('emailtemplate.list') }}',
                dataSrc: function(json) {
                    console.log("Data fetched from server:", json.data);
                    return json.data;
                },
                processing: true,
                serverSide: true,
            },
            columns: [{
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                }, // Auto index
                {
                    data: 'name'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
            <div>
                <strong style="background:#ccc;color:${row.mail_status == '1'?'green':'red'}; padding:5px 10px">${row.mail_status == '1'?'Active':'Inactive'}</strong>
            </div>
        `;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        let editUrl = `{{ route('emailtemplate.edit', '') }}/${row.id}`;
                        return `
            <div class="task_view">
                <a class="task_view_more d-flex align-items-center justify-content-center edit-channel"
                   data-currency-id="1"
                   href="` + editUrl + `">
                    <i class="fa fa-edit icons mr-2"></i> Edit
                </a>
            </div>
        `;
                    }
                }

            ],
            language: {
                search: "Search Email Templates:", // Customize search label
                lengthMenu: "Show _MENU_ records per page"
            }
        });

 $('#email-template-message-tab').on('click', function() {
    table.ajax.reload(); // If you're using DataTables and need to reload data via AJAX
  });

    });
</script>
