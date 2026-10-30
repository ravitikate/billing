@extends('layouts.app')

<style>
    .invalid-feedback {
        color: red;
        display: none;
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
</style>

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@php
    $addPermission = user()->permission('add_bill');
@endphp

@section('filter-section')
    <x-filters.filter-box>
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
        @if (!in_array('vendor', user_roles()))
            <div class="select-box ">
                <label class="duration mb-1">@lang('purchase::app.menu.vendorContractor')</label>
                <select id="filter_vendor_id" name="vendor_id" class="form-control select-picker" data-live-search="true">
                    <option value="" selected disabled>@lang('select vendors')</option>
                </select>
            </div>
        @endif
        <!-- DATE START -->

        <!-- DATE END -->
        <div class="select-box ">
            {{-- DATE FILTER ON --}}
            <label class="duration mb-1">@lang('app.dateRange')</label>
            <input type="text" id="datatableRange" class="form-control  p-1 f-14 f-w-500 border-additional-grey"
                placeholder="@lang('placeholders.dateRange')">
        </div>


        <!-- SEARCH BY START -->
        <div class="select-box task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
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
        <!-- SEARCH BY END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

        <!-- MORE FILTERS START -->
        <x-filters.more-filter-box>
            @if (!in_array('vendor', user_roles()))
                <div class="more-filter-items">
                    <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('purchase::app.menu.vendor')</label>
                    <div class="select-filter mb-4">
                        <div class="select-others">
                            <select class="form-control select-picker" name="vendor_id" id="filter_vendor_id"
                                data-container="body" data-live-search="true" data-size="8">
                                <option value="all">@lang('app.all')</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ mb_ucwords($vendor->primary_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.status')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="status" id="status" data-live-search="true"
                            data-container="body" data-size="8">
                            <option value="all">@lang('app.all')</option>
                            <option {{ request('status') == 'open' ? 'selected' : '' }} value="open">
                                @lang('purchase::modules.purchaseOrder.open')</option>
                            <option {{ request('status') == 'paid' ? 'selected' : '' }} value="paid">@lang('app.paid')
                            </option>
                            <option {{ request('status') == 'partial' ? 'selected' : '' }} value="partially_paid">
                                @lang('purchase::modules.purchaseBill.partially_paid')</option>
                            <option {{ request('status') == 'draft' ? 'selected' : '' }} value="draft">
                                @lang('purchase::modules.purchaseOrder.draft')</option>
                        </select>
                    </div>
                </div>
            </div>


        </x-filters.more-filter-box>
        <!-- MORE FILTERS END -->

    </x-filters.filter-box>
@endsection
@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">
            <div id="table-actions" class="d-block d-lg-flex align-items-center">
                @if ($addBillPermission == 'all' || $addBillPermission == 'added')
                    <x-forms.link-primary :link="route('bills.create')" class="mr-3" icon="plus">
                        @lang('purchase::app.menu.createBill')
                    </x-forms.link-primary>
                @endif
            </div>


            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="change-status">@lang('modules.tasks.changeStatus')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
                {{-- <div class="select-status mr-3 d-none quick-action-field" id="change-status-action">
                    <select name="status" class="form-control select-picker">
                        <option value="closed">@lang('app.closed')</option>
                        <option value="open">@lang('app.open')</option>
                    </select>
                </div> --}}
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
    <!--<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">-->
    <!--    <div class="modal-dialog modal-lg">-->
    <!--        <div class="modal-content">-->
    <!--            <form id="approvalForm">-->
    <!--                <div class="modal-header">-->
    <!--                    <h5 class="modal-title" id="approvalModalLabel">{{ __('app.debitDetails') }}</h5>-->
    <!--                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>-->
    <!--                </div>-->
    <!--                <div class="modal-body">-->
    <!--                    @csrf-->
    <!--                    <input type="hidden" id="bill_id" name="bill_id">-->

    <!--                    <div class="row">-->

    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="HoldBillAmt" class="form-label">{{ __('app.HoldBillAmt') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="HoldBillAmt"-->
    <!--                                    name="HoldBillAmt">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="AdvanceRecovery" class="form-label">{{ __('app.AdvanceRecovery') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="AdvanceRecovery"-->
    <!--                                    name="AdvanceRecovery">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="Steel" class="form-label">{{ __('app.Steel') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="Steel"-->
    <!--                                    name="Steel">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="Cement" class="form-label">{{ __('app.Cement') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="Cement"-->
    <!--                                    name="Cement">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="Electricity" class="form-label">{{ __('app.Electricity') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="Electricity"-->
    <!--                                    name="Electricity">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="OtherDebits" class="form-label">{{ __('app.OtherDebits') }}</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="OtherDebits"-->
    <!--                                    name="OtherDebits">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="SafetyDebit" class="form-label">Safety Debit</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="SafetyDebit"-->
    <!--                                    name="SafetyDebit">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="QualityDebit" class="form-label">Quality Debit</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="QualityDebit"-->
    <!--                                    name="QualityDebit">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="Housekeeping" class="form-label">Housekeeping</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="Housekeeping"-->
    <!--                                    name="Housekeeping">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-3">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="PoorPerformance" class="form-label">Poor Performance</label>-->
    <!--                                <input type="text" class="form-control height-35 f-15" id="PoorPerformance"-->
    <!--                                    name="PoorPerformance">-->
    <!--                            </div>-->
    <!--                        </div>-->
    <!--                        <div class="col-sm-6">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="stageDescription"-->
    <!--                                    class="form-label">{{ __('app.stageDescription') }}</label>-->
    <!--                                <textarea class="form-control" name="stageDescription" id="stageDescription" rows="2" required></textarea>-->
    <!--                                <div class="d-block invalid-feedback" id="stageDescriptionFeedback">This field is-->
    <!--                                    required.</div>-->
    <!--                            </div>-->
    <!--                        </div>-->

    <!--                        <div class="col-sm-6">-->
    <!--                            <div class="mb-3">-->
    <!--                                <label for="billDescription"-->
    <!--                                    class="form-label">{{ __('app.billDescription') }}</label>-->
    <!--                                <textarea class="form-control" name="billDescription" id="billDescription" rows="2" required></textarea>-->
    <!--                                <div class="d-block invalid-feedback" id="billDescriptionFeedback">This field is-->
    <!--                                    required.</div>-->
    <!--                            </div>-->
    <!--                        </div>-->


    <!--                    </div>-->
    <!--                </div>-->
    <!--                <div class="modal-footer">-->
    <!--                    <button type="submit" class="btn btn-primary">Submit</button>-->
    <!--                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>-->
    <!--                </div>-->
    <!--            </form>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->

     <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="approvalForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approvalModalLabel">{{ __('app.abstractApproval') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="bill_id" name="bill_id">
                        <h5>Are you sure you want to approve this bill abstract?</h5>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Yes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


  <div class="modal fade" id="rejectAbstract" tabindex="-1" aria-labelledby="rejectAbstractLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rejectAbstractForm"">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectAbstractLabel">{{ __('app.rejectAbstract') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="bill_id_reject" name="bill_id">
                        <div class="mb-3">
                            <label for="message_rejectAbstract"
                                class="form-label">{{ __('app.message_rejectAbstract') }}</label>
                            <textarea class="form-control" name="message_rejectAbstract" id="message_rejectAbstract" rows="3"></textarea>
                            <div class="d-block invalid-feedback" id="message_rejectAbstractFeedback"></div>
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

    <div class="modal fade" id="appnotavailable" tabindex="-1" aria-labelledby="appnotavailableLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="" action="{{ route('vendor.approverIsNototAvailable') }}" method="POST">
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
                            <textarea class="form-control" name="message_appnotavailable" id="message_appnotavailable" rows="3"></textarea>
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

    <div class="modal fade" id="invoiceSendVendor" tabindex="-1" aria-labelledby="invoiceSendVendorLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="invoiceSendVendorForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoiceSendVendorLabel">{{ __('app.sendInvoice') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="bill_id_invoice" name="bill_id">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="mb-3">
                                    <label for="invoiceno" class="form-label">{{ __('app.invoiceno') }}</label>
                                    <input type="text" class="form-control height-35 f-15" id="invoiceno"
                                        name="invoiceno">
                                </div>
                                <div class="invalid-feedback" id="invoicenoFeedback">This field is required.</div>
                            </div>
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
<div class="modal fade" id="rejectAbstractReason" tabindex="-1" aria-labelledby="rejectAbstractReasonLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div id="rejectAbstractReasonForm"">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectAbstractReasonLabel">{{ __('app.rejectAbstractReason') }}</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('purchase::bills.chat.groupChatModal');
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

        });

        const showTable = () => {
            window.LaravelDataTables["purchasebills-table"].draw(true);
        }

         $('#search-text-field, #status, #filter_vendor_id, #project_id, #subproject_id, #group_company_id, #recruiter, #department_id,#date_filter_on')
           .on('change keyup', function() {
            // alert('msg');
            if ($('#search-text-field').val() !== "") {
                $('#reset-filters').removeClass('d-none');
            } else if ($('#date_filter_on').val() != "start_date") {
                $('#reset-filters').removeClass('d-none');
            } else if ($('#status').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#filter_vendor_id').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
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

        // function openApprovalPopup(id) {
        //     // alert('msg');
        //     $('#bill_id').val(id);
        //     const url = "{{ route('get-debit-details', ['bill_id' => '__ID__']) }}".replace('__ID__', id);
        //     $.ajax({
        //         url: url,
        //         method: 'GET',
        //         success: function(response) {
        //             if (response.status) {
        //                 const data = response.data;
        //                 $('#HoldBillAmt').val(data.hold_bill ?? '');
        //                 $('#AdvanceRecovery').val(data.advance_recovery ?? '');
        //                 $('#Steel').val(data.steel ?? '');
        //                 $('#Cement').val(data.cement ?? '');
        //                 $('#Electricity').val(data.electricity ?? '');
        //                 $('#OtherDebits').val(data.other_debit ?? '');
        //                 $('#SafetyDebit').val(data.safety ?? '');
        //                 $('#QualityDebit').val(data.quality ?? '');
        //                 $('#Housekeeping').val(data.housekeeping ?? '');
        //                 $('#PoorPerformance').val(data.poor_performance ?? '');
        //                 $('#stageDescription').val(data.stage_description ?? '');
        //                 $('#billDescription').val(data.bill_description ?? '');
        //             } else {
        //                 // Clear all fields if no record found
        //                 $('#approvalForm').find('input, textarea').val('');
        //                 $('#bill_id').val(id);
        //             }

        //             // Open the modal
        //             let approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
        //             approvalModal.show();
        //         },
        //         error: function() {
        //             alert('Something went wrong while fetching debit details.');
        //         }
        //     });
        // }

      function openApprovalPopup(id) {
            $('#bill_id').val(id);
            let approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
            approvalModal.show();
        }


        // $('#approvalForm').on('submit', function(e) {
        //     e.preventDefault();
        //     let isValid = true;
        //     // Clear previous messages
        //     $('#stageDescriptionFeedback').hide();
        //     $('#billDescriptionFeedback').hide();
        //     // Check if fields are filled
        //     const stage = $('#stageDescription').val().trim();
        //     const bill = $('#billDescription').val().trim();
        //     if (!stage) {
        //         $('#stageDescriptionFeedback').text('This field is required.').show();
        //         isValid = false;
        //     }
        //     if (!bill) {
        //         $('#billDescriptionFeedback').text('This field is required.').show();
        //         isValid = false;
        //     }
        //     if (!isValid) return;
        //     var formData = $(this).serialize();
        //     var id = $('#bill_id').val();
        //     $.ajax({
        //         url: '{{ route('update_status_abstract') }}',
        //         type: 'POST',
        //         data: formData,
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         },
        //         beforeSend: function() {
        //             $('#loding_report').show();
        //         },
        //         success: function(response) {
        //             if (response.success) {
        //                 Swal.fire('Success!', response.message, 'success');
        //                 $('#purchasebills-table').DataTable().ajax.reload();
        //                 $('#approvalModal').modal('hide');
        //                 location.reload();
        //             } else {
        //                 Swal.fire('Error!', response.message, 'error');
        //             }
        //         },
        //         error: function() {
        //             Swal.fire('Error!', 'An error occurred while approving the item.', 'error');
        //         },
        //         complete: function() {
        //             $('#loding_report').hide();
        //         }
        //     });
        // });


        $('#approvalForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var id = $('#bill_id').val();
            $.ajax({
                url: '{{ route('update_status_abstract') }}',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $('#loding_report').show();
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        $('#purchasebills-table').DataTable().ajax.reload();
                        $('#approvalModal').modal('hide');
                        location.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An error occurred while approving the item.', 'error');
                },
                complete: function() {
                    $('#loding_report').hide();
                }
            });
        });



        function approverIsNototAvailable(id) {
            $('#billId').val(id);
            // alert(id);
            $('#appnotavailable').modal('show')
        }
        $('#invoiceSendVendorForm').on('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            const invoiceno = $('#invoiceno').val().trim();
            if (!invoiceno) {
                $('#invoicenoFeedback').text('This field is required.').show();
                isValid = false;
            }
            if (!isValid) return;
            var formData = $(this).serialize();
            $.ajax({
                url: '{{ route('update_status_pending') }}', // invoice send route
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $('#loding_report').show();
                },
                success: function(response) {
                    // console.log(response);
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        $('#purchasebills-table').DataTable().ajax.reload();
                        $('#invoiceSendVendor').modal('hide');
                        location.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An error occurred while approving the item.', 'error');
                },
                complete: function() {
                    $('#loding_report').hide();
                }
            });

        })


        function sendInvoiceFromVendor(id) {
            let invoiceSendVendor = new bootstrap.Modal(document.getElementById('invoiceSendVendor'));
            invoiceSendVendor.show();
            $('#bill_id_invoice').val(id);
            $('#invoicenoFeedback').text('').hide();
            $('#invoiceSendVendorForm')[0].reset();
        }


            function openRejectPopup(id) {
            $('#rejectAbstract').modal('show');
            $('#bill_id_reject').val(id);
            $('#message_rejectAbstractFeedback').text('').hide();
            $('#rejectAbstractForm')[0].reset();
        }
        $('#rejectAbstractForm').on('submit', function(e) {
            e.preventDefault();
            let isValid = true;

            const stage = $('#message_rejectAbstract').val();
            if (!stage) {
                $('#message_rejectAbstractFeedback').text('This field is required.').show();
                isValid = false;
            }

            if (!isValid) return;
            var formData = $(this).serialize();
            var id = $('#bill_id').val();
            $.ajax({
                url: '{{ route('update_reject_status_abstract') }}',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $('#loding_report').show();
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        $('#purchasebills-table').DataTable().ajax.reload();
                        $('#approvalModal').modal('hide');
                        location.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An error occurred while approving the item.', 'error');
                },
                complete: function() {
                    $('#loding_report').hide();
                }
            });
        });
           function openRejectReason(id) {
    $('#rejectAbstractReason').modal('show');

    $.ajax({
        url: "{{ route('get-abstract-reject-reason', ['bill_id' => '__ID__']) }}".replace('__ID__', id),
        method: 'GET',
        success: function(response) {
            const modalBody = $('#rejectAbstractReasonForm').find('.modal-body');
            modalBody.empty();

            if (response.status && response.data.length > 0) {
                let html = '<div class="container mt-3">';
                response.data.forEach(function(data) {
                    html += `
                        <div class="d-flex flex-column border rounded p-2 mb-2 bg-light">
                            <div class="d-flex justify-content-between">
                                <strong class="text-primary">${data.user_name}</strong>
                                <small class="text-muted">${data.formatted_date}</small>
                            </div>
                            <div class="mt-1">
                                <p class="mb-0">Message: ${data.reject_reason}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                modalBody.html(html);
            } else {
                modalBody.html('<p>No reject reasons provided.</p>');
            }
        },
        error: function() {
            alert('Something went wrong while fetching reject reasons.');
        }
    });
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


    </script>
    @include('purchase::bills.chat.chat_js');

    @include('purchase::bills.comp.billBreakdown')
    @include('purchase::bills.comp.billBreakdownjs')
    @include('purchase::bills.comp.characterLimitjs')
@endpush
