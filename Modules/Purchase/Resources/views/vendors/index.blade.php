@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush
<style>
    .d-lg-flex.d-md-flex.d-block.flex-wrap.filter-box.bg-white.client-list-filter {
        gap: 10px 20px;
        padding-top: 20px;
        padding-bottom: 20px;
    }

    .select-box {
        width: calc(25% - 20px) !important;
    }
</style>
@php
    $addPermission = user()->permission('add_vendor');
@endphp

@section('filter-section')
    <x-filters.filter-box>
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
        <div class="select-box ">
            <label class="duration mb-1">@lang('purchase::app.menu.vendorContractor')</label>
            <select id="filter_vendor_id" name="vendor_id" class="form-control select-picker" data-live-search="true">
                <option value="" selected disabled>@lang('select vendors')</option>
            </select>
        </div>
        <!-- DATE START -->

        <!-- DATE END -->
        <div class="select-box ">
            {{-- DATE FILTER ON --}}
            <label class="duration mb-1">@lang('app.dateRange')</label>
            <input type="text" id="datatableRange" class="form-control  p-1 f-14 f-w-500 border-additional-grey"
                placeholder="@lang('placeholders.dateRange')">
        </div>

        <div class="select-box  task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
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
        {{-- RESET BUTTON --}}
        <div class="select-box pt-3">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- SEARCH BY START -->

        <!-- SEARCH BY END -->



    </x-filters.filter-box>
@endsection
@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">

            <div id="table-actions" class="d-block d-lg-flex align-items-center">
                @if ($addPermission == 'all' || $addPermission == 'added')
                    <x-forms.link-primary :link="route('vendors.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('purchase::app.menu.addVendor')
                    </x-forms.link-primary>
                @endif
            </div>

            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
            </x-datatable.actions>

        </div>
        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection
@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('#vendors-table').on('preXhr.dt', function(e, settings, data) {
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

            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['searchText'] = searchText;
            data['date_filter_on'] = date_filter_on;
            data['vendor_id'] = $('#filter_vendor_id').val();
            data['project_id'] = $('#project_id').val();
            data['subproject_id'] = $('#subproject_id').val();
            data['group_company_id'] = $('#group_company_id').val();

        });

        const showTable = () => {
            window.LaravelDataTables["vendors-table"].draw(true);
        }
        $(document).ready(function() {

            // Subproject Validation Based on Project Selection
            $('#subproject_id, #project_id').on('change', function() {
                const projectId = $('#project_id').val();
                const subprojectId = $('#subproject_id').val();

                $.ajax({
                    url: "{{ route('projects.checkBHforproject') }}",
                    type: 'GET',
                    data: {
                        project_id: projectId,
                        subproject_id: subprojectId
                    },
                    success: function(response) {
                        if (response.status === 'error') {
                            alert(response.message);
                            $('#project_id').val('').selectpicker('refresh');
                            $('#subproject_id').html(
                                    '<option value="">@lang('placeholders.subprojects')</option>')
                                .selectpicker('refresh');
                        }

                        if (response.status === 'error1') {
                            alert(response.message);
                            $('#subproject_id').val('').selectpicker('refresh');
                        }
                    }
                });
            });

            // Load Billing Company Based on Group Company
            $('#group_company_id').on('change', function() {
                const groupCompanyId = $(this).val();

                $('#billing_company_id').html('<option value="">@lang('placeholders.billingcompany')</option>');

                if (groupCompanyId) {
                    $.ajax({
                        url: '{{ route('get-billing-companies') }}',
                        type: 'GET',
                        data: {
                            company_group_id: groupCompanyId
                        },
                        success: function(response) {
                            // console.log(response);

                            $.each(response, function(key, value) {
                                $('#billing_company_id').append('<option value="' +
                                    value.id + '">' + value.billing_company_name +
                                    '</option>');
                            });

                            $('#billing_company_id').selectpicker('refresh');
                        }
                    });
                }
            });

            // Load Subprojects when Project changes
            $('#project_id').on('change', function() {
                const projectId = $(this).val();

                $('#subproject_id').html('<option value="">@lang('placeholders.subprojects')</option>');

                if (projectId) {
                    $.ajax({
                        url: "{{ route('get.subProjects') }}",
                        type: 'GET',
                        data: {
                            project_id: projectId
                        },
                        success: function(response) {

                            // console.log(response);

                            const $subprojectSelect = $('#subproject_id');
                            const defaultOption = '<option value="">--</option>';
                            $subprojectSelect.empty().append(defaultOption);
                            const $filter_vendor_id = $('#filter_vendor_id');
                            $filter_vendor_id.empty().append(defaultOption);

                            if (response.subProjects && response.subProjects.length > 0) {
                                $.each(response.subProjects, function(index, subproject) {
                                    $subprojectSelect.append(`
                                <option data-content="${subproject.heading || ''}"
                                        value="${subproject.id}">
                                    ${subproject.heading || 'No Heading'}
                                </option>
                            `);
                                });
                            } else {
                                $subprojectSelect.append(
                                    '<option value="" disabled selected>No Sub Projects Found</option>'
                                );
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
                                $.each(response.getbilling, function(index, billing) {
                                    $billingSelect.append(`
                                <option value="${billing.id}">${billing.billing_company_name}</option>
                            `);
                                });
                            } else {
                                $billingSelect.append(
                                    '<option value="" disabled selected>No Billing Companies Found</option>'
                                );
                            }

                            // Set client dropdown (getClient is a single object)



                            const $groupCompanySelect = $('#group_company_id');
                            $groupCompanySelect.empty();
                            // Populate Group Company Dropdown
                            $groupCompanySelect.empty().append('<option value="">--</option>');

                            if (Array.isArray(response.groupCompany)) {
                                response.groupCompany.forEach(company => {
                                    const selected = response.getClient && response
                                        .getClient.id === company.id ? 'selected' : '';
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

            $('#subproject_id').on('change', function() {
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
                        success: function(response) {
                            // VENDORS
                            let $vendorDropdown = $('#filter_vendor_id');
                            $vendorDropdown.empty();
                            $vendorDropdown.append(
                                '<option value="all">@lang('app.all')</option>');
                            response.vendors.forEach(function(vendor) {
                                $vendorDropdown.append(
                                    `<option value="${vendor.vendor_id}">${vendor.primary_name}</option>`
                                );
                            });
                            $vendorDropdown.selectpicker('refresh');

                            // BILLING COMPANIES
                            let $billingDropdown = $('#billing_company_id');
                            $billingDropdown.empty();
                            $billingDropdown.append(
                                '<option value="">@lang('placeholders.billingcompany')</option>');
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

        });
        $('#search-text-field, #status, #filter_vendor_id, #project_id, #subproject_id, #group_company_id, #recruiter, #department_id,#date_filter_on')
            .on('change keyup', function() {
                if ($('#search-text-field').val() !== "") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#date_filter_on').val() != "start_date") {
                    $('#reset-filters').removeClass('d-none');
                } else {
                    $('#reset-filters').addClass('d-none');
                }
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

        $('body').on('click', '#reset-filters', function() {
            $('#filter-form')[0].reset();
            $('.filter-box #date_filter_on').val('start_date');
            $('.filter-box #status').val('not finished');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });
        $('body').on('click', '#reset-filters-2', function() {
            $('#filter-form')[0].reset();
            $('.filter-box #date_filter_on').val('start_date');
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
        $('body').on('click', '#quick-action-apply', function() {
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
        const applyQuickAction = () => {
            var rowdIds = $("#vendors-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            const url = "{{ route('vendors.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };
        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('vendor-id');
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
                    var url = "{{ route('vendors.destroy', ':id') }}";
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
    </script>
@endpush
