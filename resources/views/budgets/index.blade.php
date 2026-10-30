@extends('layouts.app')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    dropdown.bootstrap-select.form-control.select-picker {
    margin-top: 5 !important; 
}
button.btn.dropdown-toggle.btn-light{
     margin-top: 5 !important; 
}

.select-box.d-flex.pr-2.border-right-grey.border-right-grey-sm-0 {
    padding-left: 10px;
}
</style>

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')

    <x-filters.filter-box>
        
        <!-- DATE END -->
        
        {{-- PROJECT --}}
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('app.project')
            </p>
            <div class="select-status d-flex">
                <select id="project_id" name="project_id" 
                        class="form-control select-picker" 
                        data-live-search="true">
                    <option value="">@lang('placeholders.projects')</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">
                            {{ $project->project_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        {{-- SUBPROJECT --}}
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('app.subproject')
            </p>
            <div class="select-status d-flex">
                <select id="subproject_id" name="subproject_id" 
                        class="form-control select-picker" 
                        data-live-search="true">
                    <option value="">@lang('placeholders.subprojects')</option>
                </select>
            </div>
        </div>

        
 

        <!-- SEARCH BY TASK START -->
        <div class="task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                        placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <!-- SEARCH BY TASK END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

    </x-filters.filter-box>

@endsection

@php
$addEstimatePermission = user()->permission('add_estimates');
$addEstimateRequestPermission = user()->permission('add_estimate_request');
$viewEstimateRequestPermission = user()->permission('view_estimate_request');
$addBudgetPermission = user()->permission('add_budget');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-block d-lg-flex d-md-flex justify-content-between">
            <div id="table-actions" class="flex-grow-1 align-items-center mb-2 mb-lg-0 mb-md-0">
                 @if ($addBudgetPermission == 'all' || $addBudgetPermission == 'added')  
                    <x-forms.link-primary :link="route('budget.create')" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0" icon="plus">
                         Create Budget
                    </x-forms.link-primary>
 
                 @endif  
                @if (in_array('client', user_roles()) && ($addEstimateRequestPermission == 'all' || $addEstimateRequestPermission == 'added'))
                    <x-forms.link-primary :link="route('estimate-request.create')" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0 openRightModal" icon="plus">
                        @lang('modules.estimateRequest.createEstimateRequest')
                    </x-forms.link-primary>
                @endif
            </div>

            <div class="btn-group mt-2 mt-lg-0 mt-md-0 ml-0 ml-lg-3 ml-md-3" role="group">
                @if (in_array($viewEstimateRequestPermission, ['all', 'added', 'owned', 'both']))
                    <a href="{{ route('estimate-request.index') }}" class=" btn btn-secondary f-14"
                    data-toggle="tooltip" data-original-title="@lang('modules.estimateRequest.estimateRequests')">
                    <i class="side-icon fa fa-file-invoice-dollar"></i></a>
                @endif

                <a href="javascript:;" class="img-lightbox btn btn-secondary f-14"
                data-image-url="{{ asset('img/estimate-lc.png') }}" data-toggle="tooltip"
                data-original-title="@lang('app.howItWorks')"><i class="side-icon bi bi-question-circle"></i></a>
            </div>

        </div>

        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">
            {{-- {!! $dataTable->table() !!} --}}
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}


        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Rejection Reason</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="rejection-comment">
        Loading...
      </div>
    </div>
  </div>
</div>


@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>
    <script>
        var clipboard = new ClipboardJS('.btn-copy');

        clipboard.on('success', function(e) {
            Swal.fire({
                icon: 'success',
                text: '@lang("app.copied")',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
            })
        });
    </script>
    <script>
    
        $('#budgetHeadsTable').on('preXhr.dt', function(e, settings, data) {
            

            var status = $('#filter-status').val();
            var searchText = $('#search-text-field').val();

           
            data['status'] = status;
            data['project_id'] = $('#project_id').val();
            data['subproject_id'] = $('#subproject_id').val();
            
            data['searchText'] = searchText;
        });
        const showTable = () => {
            window.LaravelDataTables["budgetHeadsTable"].draw(true);
        }

        $('#filter-status,#project_id, #subproject_id')
            .on('change keyup',
                function() {
                    if ($('#filter-status').val() != "all" || $('#project_id').val() !== "" ||
                    $('#subproject_id').val() !== "" ) {
                        $('#reset-filters').removeClass('d-none');
                        showTable();
                    } else {
                        $('#reset-filters').addClass('d-none');
                        showTable();
                    }
                });

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters,#reset-filters-2').click(function() {
            $('#filter-form')[0].reset();

            $('#project_id').val('');
            $('#subproject_id').val('');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            
            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
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
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        $('body').on('click', '.change-status', function() {
            var id = $(this).data('estimate-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.estimateCancelText')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmCancel')",
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
                    var url = "{{ route('estimates.change_status', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'GET',
                        url: url,
                        container: '#budgetHeadsTable',
                        blockUI: true,
                        success: function(response) {
                            if (response.status == "success") {
                                window.LaravelDataTables["budgetHeadsTable"].draw(true);
                            }
                        }
                    });
                }
            });
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('estimate-id');
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
                    var url = "{{ route('estimates.destroy', ':id') }}";
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

        const applyQuickAction = () => {
            var rowdIds = $("#budgetHeadsTable input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('invoices.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                blockUI: true,
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                    }
                }
            })
        };

        $('body').on('click', '.sendButton', function() {
            var id = $(this).data('estimate-id');
            var url = "{{ route('estimates.send_estimate', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                type: 'POST',
                url: url,
                container: '#budgetHeadsTable',
                blockUI: true,
                data: {
                    '_token': token
                },
                success: function(response) {
                    if (response.status == "success") {
                        window.LaravelDataTables["budgetHeadsTable"].draw(true);
                    }
                }
            });
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
    </script>
    
    <script>
        $(document).on('click', '[data-bs-target="#rejectReasonModal"]', function () {
            let budgetId = $(this).data('id'); 
            $.ajax({
                url: 'getRejectionComment/' + budgetId,
                method: 'GET',
                success: function (response) { 
                    $('#rejection-comment').text(response.comment || 'No comment found.');
                },
                error: function () {
                    $('#rejection-comment').text('Failed to fetch comment.');
                }
            });
        });
    </script>
@endpush
