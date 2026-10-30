<script>
    $(document).ready(function() {
        $('#instantDebit').click(function() {
            $('#instantDebitModal').modal('show');
        });
        $('.closeMod').click(function() {
            $('.modal').modal('hide');
        });

        const $projectSelect = $('#project_id');
        const $subprojectSelect = $('#subproject_id');
        var defaultOption = '<option value="" disabled selected >-Select Sub Project-</option>';

        function loadSubprojects(projectId) {
            if (projectId) {
                $.ajax({
                    url: "{{ route('get.subProjects') }}",
                    type: "GET",
                    data: {
                        project_id: projectId
                    },
                   success: function(response) { 
                        $subprojectSelect.empty().append('<option value="">--</option>');
                    
                        if (response.subProjects && response.subProjects.length > 0) {
                            $.each(response.subProjects, function (index, subproject) {
                                $subprojectSelect.append(`
                                    <option data-content="${subproject.heading || ''}"
                                            value="${subproject.id}">
                                        ${subproject.heading || 'No Heading'}
                                    </option>
                                `);
                            });
                        } else {
                            $subprojectSelect.append('<option value="" disabled selected>No Sub Projects Found</option>');
                        }
                    
                        $('.select-picker').selectpicker('refresh');
                    },

                    error: function(xhr, status, error) {
                        console.error('Error fetching subprojects:', status, error);
                        $subprojectSelect.html(
                            '<option value="">Error loading sub-projects</option>');
                    }
                });
            } else {
                $subprojectSelect.html('<option value="">Select a Project First</option>');
            }
        }

        const initialProjectId = $projectSelect.val();
        if (initialProjectId) {
            $subprojectSelect.empty().append(defaultOption);
            loadSubprojects(initialProjectId);
        }

        $projectSelect.on('change', function() {
            const projectId = $(this).val();
            $subprojectSelect.empty().append(defaultOption);
            $('#vendor_id').empty().append('<option value="" disabled selected >--</option>');
            $('#instantDebitAmount').val('')
            $('#purchaseOrder').empty().append(
                '<option value="" disabled selected >--</option>');
            loadSubprojects(projectId);
            $('#no_permission').text('');
            $('#smt_btn').show();
        });
    });
</script>
<script>
    $('#subproject_id').on('change', function() {
        $('#vendor_id').empty().append('<option value="" disabled selected >-Select Vendor Name-</option>');
        $('#purchaseOrder').empty().append(
            '<option value="" disabled selected >--</option>');
        const project_id = $('#project_id').val();
        const subproject_id = $(this).val();

        $('#no_permission').text('');
        $('#smt_btn').show();
        $.ajax({
            url: "{{ route('getVendorDetails') }}",
            type: "get",
            data: {
                project_id: project_id,
                subproject_id: subproject_id
            },
            success: function(response) {

                if (response && response.length > 0) {
                    $.each(response, function(index, item) {
                        // console.log(item);

                        $('#vendor_id').append(`
                        <option data-content="${item.primary_name}"
                                value="${item.vendor_id}">
                            ${item.primary_name || 'No Po'}
                        </option>
                    `);
                    });
                    $('.select-picker').selectpicker('refresh');
                }
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    });

    $('#vendor_id').on('change', function() {
        $('#purchaseOrder').empty().append(
            '<option value="" disabled selected >-Select Purchase Order-</option>');
        const project_id = $('#project_id').val();
        const subproject_id = $('#subproject_id').val();
        const vendor_id = $(this).val();
        $('#no_permission').text('');
        $('#smt_btn').show();
        $.ajax({
            url: "{{ route('getpurchaseOrder') }}",
            type: "POST",
            data: {
                project_id: project_id,
                subproject_id: subproject_id,
                vendor_id: vendor_id,
                _token: "{{ csrf_token() }}"
            },

            success: function(response) {


                if (response && response.length > 0) {
                    $.each(response, function(index, item) {
                        // console.log(item);

                        $('#purchaseOrder').append(`
                        <option data-content="${item.po_number}"
                                value="${item.id}">
                            ${item.po_number || 'No Po'}
                        </option>
                    `);
                    });
                    $('.select-picker').selectpicker('refresh');
                }
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    });
 
 
    
    $('#purchaseOrder').on('change', function() {
        const project_id = $('#project_id').val();
        const subproject_id = $('#subproject_id').val();
        const vendor_id = $('#vendor_id').val();
        const po_id = $(this).val();
        $('#no_permission').text('');
        $('#smt_btn').show();
        const BASE_URL = "{{ url('/') }}/";
        $.ajax({
            url: "{{ route('getInstantDebitAmt') }}",
            type: "post",
            data: {
                project_id: project_id,
                subproject_id: subproject_id,
                vendor_id: vendor_id,
                po_id: po_id,
                 _token: "{{ csrf_token() }}"
            },
 
 
            success: function(data) {
                console.log(data.status);
                
                if (data.status === 'error') {
        $('#messageBox').text(data.message).show();
        $('#htmlInstantDebitDetail').empty();
        return;
    } else {
        $('#messageBox').hide(); // Hide previous messages
    }
 
                let res = Array.isArray(data.instant_debits) ? data.instant_debits : [];
                if (res.length > 0) {
 
                    let html = `<tr>
                                    <td colspan="4"><strong>{{ __('app.preinstantDebit') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>#</strong></td>
                                    <td><strong>{{ __('app.instantDebitAmount') }}</strong></td>
                                    <td><strong>{{ __('app.uploadDocument') }}</strong></td>
                                    <td><strong>{{ __('app.message') }}</strong></td>
                                    <td><strong>{{ __('app.action') }}</strong></td>
                                </tr>`;
                    res.forEach(function(item, index) {
                        let actionButtons = '';
                        if (data.current_priority_id > item.priority_id || data.p_role_count == data.current_priority_id) {
                            actionButtons =
                                `
                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteInstantDebit(${item.id})">Delete</button>
                            <button type="button" class="btn btn-primary btn-xs" onclick="editInstantDebit(${item.id})">Edit</button>`;
                        } else {
                            actionButtons =
                                `<span class="text-muted">No actions available</span>`;
                        }
                        html += `<tr>
                                  <td>${index + 1}</td>
                                    <td>${(item.instantdebitamount)}</td>
                                    <td><a href="${BASE_URL}${item.file}" target="_blank">View File</a></td>
                                    <td>${item.message}</td>
                                  <td>${actionButtons}</td>
                                </tr> `;
                    });
 
                    $('#htmlInstantDebitDetail').html(html);
                } else {
                    $('#htmlInstantDebitDetail').empty();
                }
 
 
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    });
 



    function editInstantDebit(id) {
        $('#instantDebitModal').modal('hide');
        $('#editInstantDebitModal').modal('show');
        const BASE_URL = "{{ url('/') }}/";

        $.ajax({
            url: "{{ route('getInstantDebitDetails') }}",
            type: "POST",
            data: {
                id: id,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                console.log(res);
                html = `<tr>
                        <td><strong>{{ __('app.project') }} : </strong> ${res.project_name} </td>
                        <td><strong>{{ __('app.subproject') }} : </strong> ${res.task_short_code} ${res.heading} </td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('app.vendorName') }} : </strong> ${res.vendorname} </td>
                        <td><strong>{{ __('app.purchaseOrder') }} : </strong> ${res.po_number} </td>
                    </tr>`;
                $('#htmlInstantDebitDetailx').append(html);
                $('#editinstantDebitId').val(res.id);
                $('#editinstantDebitAmount').val(res.instantdebitamount);
                $('#editstatusMessage').val(res.message);
                $('#old_file').val(res.file);
                $('#viewFile').html(`<a href="${BASE_URL}${res.file}" target="_blank">View File</a>`)
            },
            error: function(xhr, status, error) {
                console.error('Error fetching instant debit details:', status, error);
            }
        });

        // Additional logic for editing instant debit can be added here
    }
    function deleteInstantDebit(id) {

        const BASE_URL = "{{ url('/') }}/";

        $.ajax({
            url: "{{ route('deleteInstantDebitDetails') }}",
            type: "POST",
            data: {
                id: id,
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                console.log(res);
                if(res.success == true){
                    alert('Instant Debit Deleted Successfully');
                    location.reload();
                    $('#instantDebitModal').modal('hide');
                } else {
                    alert('Error deleting Instant Debit');
                }

            },
            error: function(xhr, status, error) {
                console.error('Error fetching instant debit details:', status, error);
            }
        });

        // Additional logic for editing instant debit can be added here
    }
</script>
