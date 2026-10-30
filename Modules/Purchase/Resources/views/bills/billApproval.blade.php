@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush
<style>
    .pointer {
        cursor: pointer;
    }

    .user-name {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        display: inline-block;
        width: 88%;
    }

    .user-status {
        display: flex;
        padding: 0;
        margin: 0;
        align-items: center;
        justify-content: space-between;
    }

    #allHoldContainer {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
</style>
<style>
    .d-lg-flex.d-md-flex.d-block.flex-wrap.filter-box.bg-white.client-list-filter {
        gap: 10px 20px;
        padding-top: 20px;
        padding-bottom: 20px;
    }

    .select-box {
        width: calc(25% - 20px) !important;
    }
    div#totals {
    display: flex !important
;
    flex-direction: row;
    justify-content: space-between;
    flex-wrap: wrap;
    font-size: 15px;
    align-items: center;
}
div#totals p{
    margin: 0
}
</style>
@php
    $addPermission = user()->permission('add_bill');
     $billApprovalFiltter = user()->permission('bill_approval_filtter');
@endphp

@section('filter-section')

   <x-filters.filter-box>


         @if ($billApprovalFiltter == 'all')
        {{-- PROJECT --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('app.project')</label>
            <select id="project_id" name="project_id" class="form-control select-picker" data-live-search="true">
                <option value="">@lang('placeholders.projects')</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                @endforeach
            </select>
        </div>
        {{-- SUBPROJECT --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('app.subproject')</label>
            <select id="subproject_id" name="subproject_id" class="form-control select-picker" data-live-search="true">
                <option value="">@lang('placeholders.subprojects')</option>
            </select>
        </div>
        {{-- GROUP COMPANY --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('app.group_company')</label>
            <select id="group_company_id" name="group_company_id" class="form-control select-picker"
                data-live-search="true">
                <option value="">@lang('placeholders.groupcompany')</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->company_name }}</option>
                @endforeach
            </select>
        </div>
        {{-- BILLING COMPANY --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('app.billingcompany')</label>
            <select id="billing_company_id" name="billing_company_id" class="form-control select-picker"
                data-live-search="true">
                <option value="">@lang('placeholders.billingcompany')</option>
            </select>
        </div>
        {{-- VENDOR --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('purchase::app.menu.vendorContractor')</label>
            <select id="filter_vendor_id" name="vendor_id" class="form-control select-picker" data-live-search="true">
                <option value="all">@lang('app.all')</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ mb_ucwords($vendor->primary_name) }}</option>
                @endforeach
            </select>
        </div>
        @endif
        {{-- STATUS --}}
        <!--<div class="select-box ">-->
        <!--    <label class="duration mb-1">@lang('app.status')</label>-->
        <!--    <select id="status" name="status" class="form-control select-picker" data-live-search="true">-->
        <!--        <option value="all">-</option>-->
        <!--        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>-->
        <!--            @lang('app.paid')-->
        <!--        </option>-->
        <!--        <option value="due" {{ request('status') == 'due' ? 'selected' : '' }}>-->
        <!--            @lang('app.due')-->
        <!--        </option>-->
        <!--        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>-->
        <!--            @lang('app.approve')-->
        <!--        </option>-->
        <!--        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>-->
        <!--            @lang('app.pending')-->
        <!--        </option>-->
        <!--        <option value="abstract" {{ request('status') == 'abstract' ? 'selected' : '' }}>-->
        <!--            @lang('app.abstract')-->
        <!--        </option>-->

        <!--    </select>-->
        <!--</div>-->
  {{-- DATE RANGE --}}
        <div class="select-box ">
            <label class="duration mb-1">@lang('app.dateRange')</label>
            <input type="text" id="datatableRange" class="form-control  p-1 f-14 f-w-500 border-additional-grey"
                placeholder="@lang('placeholders.dateRange')">
        </div>
        {{-- RESET BUTTON --}}
        <div class="select-box pt-3">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>

    </x-filters.filter-box>


@endsection
@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="card p-2" id="totals">
            <h4>Totals</h4>
            <p>Total Net Payable: ₹ <strong id="total_netpayableamount">0</strong></p>
            <p>Total Paid: ₹ <strong id="total_paid_amount">0</strong></p>
            <p>Pending Amount: ₹ <strong id="pending_amount">0</strong></p>
        </div>
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">

            <button type="button" id="open-modal-button" class="btn btn-primary" style="display: none;" data-toggle="modal"
                data-target="#bulkActionModal">
                Approve
            </button>



        </div>
        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->
    {{-- {{dd(session('user_roles'));
}} --}}

    <div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <!-- Dynamic title based on status -->
                    <h5 class="modal-title" id="approvalModalLabel">Are you sure you want to proceed?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="statusChangeForm" enctype="multipart/form-data">
                    @if (in_array('billing-hod', session('user_roles', [])) || in_array('billing', session('user_roles', [])))
                        <div class="p-2" id="referenceId">
                            <label for="documentType" class="form-label">Reference Number:</label>
                            <input type="text" id="reference_no" name="reference_no" class="form-control"
                                placeholder="Enter Reference Number">
                        </div>
                    @endif


                    <div class="modal-body">


                        <!-- Confirmation Message (Only visible when status == 2) -->
                        <div id="confirmationMessage" class="alert alert-warning" style="display: none;">
                            Are you sure you want to approve the item bill?
                        </div>

                        <!-- Message Input (Hidden for status 2) -->
                        <div class="mb-3" id="messageField">
                            <label for="statusMessage" class="form-label">Message:</label>
                            <textarea id="statusMessage" name="message" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Document Type Input (Hidden for status 2) -->
                        <div class="mb-3" id="documentTypeField">
                            <label for="documentType" class="form-label">Document Type:</label>
                            <input type="text" id="documentType" name="document_name" class="form-control"
                                placeholder="Enter document type" required>
                        </div>

                        <!-- File Upload (Hidden for status 2) -->
                        <div class="mb-3" id="fileUploadField">
                            <label for="documentFile" class="form-label">Upload Document:</label>
                            <input type="file" id="documentFile" name="file" class="form-control"
                                accept=".pdf,.jpg,.png" required>
                            <small class="form-text text-muted">Allowed file types: PDF, JPG, PNG</small>
                        </div>

                        <!-- Hidden Inputs -->
                        <input type="hidden" id="modalId" name="row_id">
                        <input type="hidden" id="approve_status" name="approve_status">
                        <input type="hidden" id="approveReject" name="approveReject">
                        <input type="hidden" id="projectIds" name="project_id">
                        <input type="hidden" id="department_ids" name="dept_id">
                        <input type="hidden" id="priority_counts" name="priority_count">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <!-- Modal -->
    <!-- Modal -->
    <div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog" aria-labelledby="bulkActionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkActionModalLabel">Confirm Bulk Action</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p id="selected-ids-text"></p>
                    <p>Do you want to continue?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirm-bulk-action" class="btn btn-primary">Yes, Continue</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="appnotavailable" tabindex="-1" aria-labelledby="appnotavailableLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="" action="{{ route('vendor.approverIsNototAvailablePermision') }}" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appnotavailableLabel">{{ __('app.appnotavailable') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="billId" name="bill_id">
                        <div class="mb-3">
                            <label for="message_appnotavailable"
                                class="form-label">{{ __('app.message_appnotavailable') }}</label>
                            <textarea class="form-control" name="message_appnotavailable" id="message_appnotavailable" rows=3></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    @include('purchase::bills.comp.debitDetails')
    @include('purchase::bills.chat.groupChatModal')
@endsection
@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('#purchasebills-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = $('#datatableRange').val();

            let endDate;

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ $company->moment_format }}');
                endDate = dateRangePicker.endDate.format('{{ $company->moment_format }}');
            }
            const searchText = $('#search-text-field').val();
            const date_filter_on = $('#date_filter_on').val();

            var status = $('#status').val();
            var vendor_id = $('#filter_vendor_id').val();

            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['searchText'] = searchText;
            data['date_filter_on'] = date_filter_on;
            data['status'] = status;
            data['vendor_id'] = vendor_id;
            data['project_id'] = $('#project_id').val();
            data['subproject_id'] = $('#subproject_id').val();
            data['group_company_id'] = $('#group_company_id').val();
            data['billing_company_id'] = $('#billing_company_id').val();

        });

        const showTable = () => {
            window.LaravelDataTables["purchasebills-table"].draw(true);
        }

        $('#search-text-field, #date_filter_on, #status, #filter_vendor_id, #project_id, #subproject_id, #group_company_id, #billing_company_id')
            .on('change keyup', function () {
                const shouldShowReset =
                    $('#search-text-field').val() !== "" ||
                    $('#date_filter_on').val() !== "start_date" ||
                    $('#status').val() !== "all" ||
                    $('#filter_vendor_id').val() !== "all" ||
                    $('#project_id').val() !== "" ||
                    $('#subproject_id').val() !== "" ||
                    $('#group_company_id').val() !== "" ||
                    $('#billing_company_id').val() !== "";

                $('#reset-filters').toggleClass('d-none', !shouldShowReset);
                showTable();
            });

       $('body').on('click', '#reset-filters, #reset-filters-2', function () {
            $('#filter-form')[0].reset();

            // Reset specific fields
            $('#search-text-field').val('');
            $('#date_filter_on').val('start_date');
            $('#status').val('all');
            $('#filter_vendor_id').val('all');
            $('#project_id').val('');
            $('#subproject_id').val('');
            $('#group_company_id').val('');
            $('#billing_company_id').val('');
            $('#datatableRange').val('');

            // Refresh the select pickers
            $('.filter-box .select-picker').selectpicker("refresh");

            // Hide reset button
            $('#reset-filters').addClass('d-none');

            // Redraw DataTable
            showTable();
        });
        $('body').on('click', '#reset-filters-2', function() {
            $('#filter-form')[0].reset();
            $('.filter-box #date_filter_on').val('start_date');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('bill-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('bills.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        $('body').on('click', '.sendButton', function() {
            var id = $(this).data('bill-id');
            var url = "{{ route('bills.send_invoice', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                type: 'POST',
                url: url,
                container: '#purchasebills-table',
                blockUI: true,
                data: {
                    '_token': token,
                },
                success: function(response) {
                    if (response.status == "success") {
                        showTable();
                    }
                }
            });
        });

        function openApprovalPopup(id) {

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to approve this item?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send ID to backend using AJAX
                    $.ajax({
                        url: '{{ route('submit_abstract_bill') }}', // Adjust the URL as per your route
                        type: 'POST',
                        data: {
                            id: id,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Success!', response.message, 'success');
                                $('#purchasebills-table').DataTable().ajax
                                    .reload(); // Reload DataTable if using DataTables
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(error) {
                            Swal.fire(
                                'Error!',
                                'An error occurred while approving the item.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

         $(document).on('change', '.status-dropdown', function() {
            // alert();
            let status = $(this).val();
            let id = $(this).data('id');
            let projectId = $(this).data('project-id');
            let approve_status = $(this).data('approve-status');
            let department_id = $(this).data('department-id');
            let priority_count = $(this).data('priority-count');



            if (status == 2 || status == 3) {
                $('#approvalModal').modal('show');
                $('#approveReject').val(status);
                $('#approve_status').val(approve_status);
                $('#modalId').val(id);
                $('#projectIds').val(projectId);
                $('#department_ids').val(department_id);
                $('#priority_counts').val(priority_count);

                if (status == 2) {
                    // Hide the modal body and show a confirmation message
                    $('.modal-body').hide();
                    $('#referenceId').show();
                    $('#confirmationMessage').show().text('Are you sure you want to approve the item bill?');
                    // Remove required attributes
                    $('#statusMessage').prop('required', false);
                    $('#documentType').prop('required', false);
                    $('#documentFile').prop('required', false);
                    $('#referenceId').prop('required', true);
                } else if (status == 3) {
                    // Show the modal body for rejection
                    $('.modal-body').show();
                    $('#confirmationMessage').hide();
                    $('#referenceId').hide();
                    // Make the message field required
                    $('#statusMessage').prop('required', true);
                    $('#documentType').prop('required', false);
                    $('#documentFile').prop('required', false);
                    $('#referenceId').prop('required', false);


                }


            }
        });
        $('#statusChangeForm').on('submit', function(e) {
            e.preventDefault();
            $('body').addClass('loading');

            let submitButton = $(this).find("button[type='submit']");
            submitButton.prop("disabled", true).text("Processing...");

            let formData = new FormData(this);

            $.ajax({
                url: '{{ route('update_status_bill') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('body').removeClass('loading');
                    submitButton.prop("disabled", false).text("Confirm");

                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Reload the page after success
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    $('body').removeClass('loading');
                    submitButton.prop("disabled", false).text("Confirm");

                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
        const dataTableRowCheckNew = (id) => {
            if ($(".select-table-row-new:checked").length > 0) {
                $("#quick-action-form").fadeIn();
                $("#open-modal-button").fadeIn();
                document.getElementById("select-all-table-new").indeterminate = true;
                $("#quick-actions").find("input, textarea, button, select").removeAttr("disabled");
                $(".select-picker").selectpicker("refresh");
            } else {
                $("#quick-action-form").fadeOut();
                $("#open-modal-button").fadeOut();
                document.getElementById("select-all-table-new").indeterminate = false;
                $("#select-all-table-new").prop("checked", false);
                resetActionButtons();
            }

            if ($("#datatable-row-new-" + id).is(":checked")) {
                $("#row-" + id).addClass("table-active");
            } else {
                $("#row-" + id).removeClass("table-active");
            }
        };

        const selectAllTableNew = (source) => {
            const checkboxes = document.getElementsByName("datatable_ids[]");

            for (let i = 0; i < checkboxes.length; i++) {
                const checkbox = checkboxes[i];
                const checkboxElement = $("#" + checkbox.id);

                if (!checkboxElement.prop('disabled')) {
                    checkbox.checked = source.checked;
                }

                if (checkboxElement.is(":checked")) {
                    checkboxElement.closest("tr").addClass("table-active");
                    $("#quick-actions").find("input, textarea, button, select").removeAttr("disabled");
                    $(".select-picker").selectpicker("refresh");
                } else {
                    checkboxElement.closest("tr").removeClass("table-active");
                    resetActionButtons();
                }
            }

            if ($(".select-table-row-new:checked").length > 0) {
                $("#quick-action-form").fadeIn();
                $("#open-modal-button").fadeIn();
            } else {
                $("#quick-action-form").fadeOut();
                $("#open-modal-button").fadeOut();
            }
        };

        $("#open-modal-button").on("click", function() {
            const selectedIds = $(".select-table-row-new:checked")
                .map(function() {
                    return $(this).val();
                })
                .get();

            if (selectedIds.length === 0) return;

            // Set the selected IDs in the modal text
            $("#selected-ids-text").html(`Are you sure you want to approve these bills?`);


            // Open the modal
            $("#bulkActionModal").modal('show');

            // Handle the confirmation button inside the modal
            $("#confirm-bulk-action").on("click", function() {
                // Submit the form via AJAX
                applyQuickActionWithIds(selectedIds);
                $("#bulkActionModal").modal('hide');
            });
        });

        const applyQuickActionWithIds = (ids) => {
            let url = "{{ route('bulk_approval') }}"; // This is your bulk approval route

            // Create a form to send the selected IDs
            let form = $("<form>", {
                method: "POST",
                action: url,
            });

            // Add CSRF token (make sure this meta tag is present in your HTML <head>)
            form.append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: $('meta[name="csrf-token"]').attr('content')
            }));

            // Add row_ids (selected IDs) as a parameter
            form.append($('<input>', {
                type: 'hidden',
                name: 'row_ids',
                value: ids.join(',')
            }));

            // Append the form to the body and submit it
            form.appendTo('body');
            form.submit();
        };
    </script>



    <script>
        function approverIsNototAvailable(id) {
            $('#billId').val(id);
            // alert(id);
            $('#appnotavailable').modal('show')
        }


        function update_debit_details(id) {
            $('#bill_Id').val(id);

            // alert(id);
            var url = "{{ route('get-bill-details', ':id') }}".replace(':id', id);
            $('#debitDetails').modal('show')
            const BASE_URL = "{{ url('/') }}/";
            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    // console.log(data);

                    $('#bill_idx').val(data.bill_id || '');
                    $('#bill_idxx').text(data.bill_id || '');
                    $('#HoldBillAmt').val(data.HoldBillAmt || '');
                    $('#AdvanceRecovery').val(data.AdvanceRecovery || '');
                    $('#Steel').val(data.Steel || '');
                    $('#Cement').val(data.Cement || '');
                    $('#Electricity').val(data.Electricity || '');
                    $('#OtherDebits').val(data.OtherDebits || '');
                    $('#SafetyDebit').val(data.SafetyDebit || '');
                    $('#QualityDebit').val(data.QualityDebit || '');
                    $('#Housekeeping').val(data.Housekeeping || '');
                    $('#PoorPerformance').val(data.PoorPerformance || '');
                    $('#stageDescription').val(data.stageDescription || '');
                    $('#billDescription').val(data.billDescription || '');
                    // $('#instantDebitAmount').val(data.instantDebitAmount || '0');

                    // if (data.pr_status === 'no_permission') {
                    //     $('#instantDebitAmount').attr('readonly', true);
                    // } else {
                    //     $('#instantDebitAmount').removeAttr('readonly');
                    // }

                    if (data.smtbtn_status === 'no_permission') {
                        $('#smtBtn').css('display', 'none');
                        $('#no_permission').text('You do not have permission to edit this bill.');

                    } else {
                        $('#smtBtn').css('display', 'block');
                        $('#no_permission').css('display', 'none');


                    }

                    var checkboxesHtml =
                        `<div class="col-sm-12"><h6>{{ __('app.releaseHoldAmt') }}</h6></div>`;

                    if (Array.isArray(data.all_hold) && data.all_hold.length > 0) {
                        checkboxesHtml += data.all_hold.map(function(item) {
                            return `<div class="col-sm-3 checkbox">
                                    <input type="checkbox" class="hold_checkbox" id="hold_${item.id}" name="hold_ids[]" value="${item.id}" data-amount="${item.HoldBillAmt}">
                                    <label for="hold_${item.id}">ID: ${item.id}, Amount: ${item.HoldBillAmt}</label>
                                </div>`;
                        }).join('');
                    } else {
                        checkboxesHtml += `<div class="text-muted">No hold items found.</div>`;
                    }

                    $('#allHoldContainer').html(checkboxesHtml);


                    let res = Array.isArray(data.instant_debits) ? data.instant_debits : [];
                    if (res.length > 0) {

                        let html = `<tr>
                                    <td colspan="4"><strong>{{ __('app.instantDebit') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>#</strong></td>
                                    <td><strong>{{ __('app.instantDebitAmount') }}</strong></td>
                                    <td><strong>{{ __('app.uploadDocument') }}</strong></td>
                                    <td><strong>{{ __('app.message') }}</strong></td>
                                    <td><strong>{{ __('app.action') }}</strong></td>
                                </tr>`;
                        res.forEach(function(item, index) {
                            console.log(data.smtbtn_status);

                            let actionButtons = '';
                            if (data.smtbtn_status === 'no_permission') {
                                actionButtons =
                                    `<span class="text-muted">No actions available</span>`;
                            } else {
                                if (data.current_priority_id > item.priority_id || data.p_role_count ==
                                    data.current_priority_id) {
                                    actionButtons =
                                        `<button type="button" class="btn btn-primary btn-xs" onclick="editInstantDebit(${item.id})">Edit</button>`;
                                } else {
                                    actionButtons =
                                        `<span class="text-muted">No actions available</span>`;
                                }
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
                error: function(xhr) {
                    console.error("Error fetching bill details:", xhr.responseText);
                    alert("Failed to load bill details.");
                }
            });


        }

         $('#group_company_id').on('change', function () {
            const groupCompanyId = $(this).val();

            $('#billing_company_id').html('<option value="">@lang("placeholders.billingcompany")</option>');

            if (groupCompanyId) {
                $.ajax({
                    url: '{{ route("get-billing-companies") }}',
                    type: 'GET',
                    data: { company_group_id: groupCompanyId },
                    success: function (response) {
                        console.log(response);
                        $.each(response, function (key, value) {
                            $('#billing_company_id').append('<option value="' + value.id + '">' + value.billing_company_name + '</option>');
                        });

                        $('#billing_company_id').selectpicker('refresh');
                    }
                });
            }
        });
         $('#project_id').on('change', function () {
            const projectId = $(this).val();

            $('#subproject_id').html('<option value="">@lang("placeholders.subprojects")</option>');

            if (projectId) {
                $.ajax({
                    url: "{{ route('get.subProjects') }}",
                    type: 'GET',
                    data: {
                            project_id: projectId
                        },
                    success: function (response) {

                        console.log(response);

                    const $subprojectSelect = $('#subproject_id');
                        const defaultOption = '<option value="">--</option>';
                        $subprojectSelect.empty().append(defaultOption);

                            const $filter_vendor_id = $('#filter_vendor_id');
                            $filter_vendor_id.empty().append(defaultOption);

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

                        if (response.vendors && response.vendors.length > 0) {
                                $.each(response.vendors, function(index, vendor) {
                                    $filter_vendor_id.append(`
                                <option data-content="${vendor.name || ''}"
                                        value="${vendor.vendor_id}">
                                    ${vendor.primary_name || 'No Name'}
                                </option>
                            `);
                                });
                            } else {
                                $filter_vendor_id.append(
                                    '<option value="" disabled selected>No Vendors Found</option>'
                                );
                            }


                        // Populate billing companies
                        const $billingSelect = $('#billing_company_id');
                        $billingSelect.empty().append(defaultOption);

                        if (response.getbilling && response.getbilling.length > 0) {
                            $.each(response.getbilling, function (index, billing) {
                                $billingSelect.append(`
                                    <option value="${billing.id}">${billing.billing_company_name}</option>
                                `);
                            });
                        } else {
                            $billingSelect.append('<option value="" disabled selected>No Billing Companies Found</option>');
                        }

                        // Set client dropdown (getClient is a single object)



                        const $groupCompanySelect = $('#group_company_id');
                        $groupCompanySelect.empty();
                        // Populate Group Company Dropdown
                        $groupCompanySelect.empty().append('<option value="">--</option>');

                    if (Array.isArray(response.groupCompany)) {
                            response.groupCompany.forEach(company => {
                                const selected = response.getClient && response.getClient.id === company.id ? 'selected' : '';
                                $groupCompanySelect.append(`
                                    <option value="${company.id}" ${selected}>
                                        ${company.company_name}
                                    </option>
                                `);
                            });
                        }

                        $('.select-picker').selectpicker('refresh');

                        // Set and hide group company
                        $('#companyGroupId').val(response.getClient.id);
                        $('#companyGroupName').val(response.getClient.company_name);

                        $('.select-picker').selectpicker('refresh');
                    }
                });
            } else {
                $('#subproject_id').selectpicker('refresh');
            }
        });
        $('#subproject_id').on('change', function () {
        const projectId = $('#project_id').val();
        const subProjectId = $(this).val();

        if (projectId && subProjectId) {
            $.ajax({
                url: "{{ route('get.vendorList') }}",
                type: 'GET',
                data: {
                    project_id: projectId,
                    subProjectId: subProjectId
                },
                success: function (response) {
                    // VENDORS
                    let $vendorDropdown = $('#filter_vendor_id');
                    $vendorDropdown.empty();
                    $vendorDropdown.append('<option value="all">Select Vendor</option>');
                    response.vendors.forEach(function(vendor) {
                        $vendorDropdown.append(
                            `<option value="${vendor.vendor_id}">${vendor.primary_name}</option>`
                        );
                    });
                    $vendorDropdown.selectpicker('refresh');

                    // BILLING COMPANIES
                    let $billingDropdown = $('#billing_company_id');
                    $billingDropdown.empty();
                    $billingDropdown.append('<option value="">@lang("placeholders.billingcompany")</option>');
                    response.billingCompanies.forEach(function(bc) {
                        $billingDropdown.append(
                            `<option value="${bc.billing_company_id}">${bc.billing_company_name}</option>`
                        );
                    });
                    $billingDropdown.selectpicker('refresh');
                },
                error: function() {
                    console.log('Failed to fetch vendors or billing companies');
                }
            });
        }
    });
     function loadTotals() {
            let dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = null;
            let endDate = null;

            if (dateRangePicker && $('#datatableRange').val() !== '') {
                startDate = dateRangePicker.startDate.format('{{ $company->moment_format }}');
                endDate = dateRangePicker.endDate.format('{{ $company->moment_format }}');
            }

            let params = {
                project_id: $('#project_id').val(),
                subproject_id: $('#subproject_id').val(),
                group_company_id: $('#group_company_id').val(),
                billing_company_id: $('#billing_company_id').val(),
                vendor_id: $('#filter_vendor_id').val(),
                status: 'pending',
                startDate: startDate,
                endDate: endDate,
                searchText: $('#search-text-field').val() || null,
                _token: '{{ csrf_token() }}'
            };
 console.log(params);

            $.ajax({
                url: "{{ route('purchase-bills.totals') }}",
                type: "POST",
                data: params,
                dataType: "json",
                beforeSend: function() {
                    // Optional: show loading indicator
                    $('#total_netpayableamount, #total_paid_amount, #pending_amount').text('Loading...');
                },
                success: function(data) {
                    console.log('Totals:', data);

                    $('#total_netpayableamount').text(
                        parseFloat(data.total_netpayableamount ?? 0).toLocaleString('en-IN')
                    );
                    $('#total_paid_amount').text(
                        parseFloat(data.total_paid_amount ?? 0).toLocaleString('en-IN')
                    );
                    $('#pending_amount').text(
                        ((data.total_netpayableamount ?? 0) - (data.total_paid_amount ?? 0)).toLocaleString(
                            'en-IN')
                    );
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching totals:', error);
                }
            });
        }

$(document).ready(function() {
    // Initial load
    loadTotals();

    // Generic change/keyup triggers for dropdowns and search field
    $('body').on('change keyup',
        '#project_id, #subproject_id, #group_company_id, #billing_company_id, #filter_vendor_id, #status, #search-text-field',
        function() {
            loadTotals();
        }
    );

    // Date range picker apply & cancel events
    $('#datatableRange').on('apply.daterangepicker cancel.daterangepicker', function() {
        loadTotals();
    });

    // Reset filters
    $('body').on('click', '#reset-filters, #reset-filters-2', function() {
        $('#filter-form')[0].reset();
        $('.select-picker').selectpicker('refresh');
        $('#datatableRange').val('');
        loadTotals();
    });
});
    </script>
    @include('purchase::bills.chat.chat_js')
    @include('purchase::bills.comp.billBreakdown')
    @include('purchase::bills.comp.billBreakdownjs')
    @include('purchase::bills.comp.characterLimitjs')


    @include('dashboard.employee.section.addinstantDebitAmount')
    @include('dashboard.employee.section.instantdebitjs')
@endpush
