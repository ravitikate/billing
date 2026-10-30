@php
    $addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.workOrder')</h4>
    </div>
    <div class="row">
                    <div class="col-sm-12">
                        <div class="d-flex justify-content-between">
                            <div>
                                <x-forms.label fieldId="download-workorder-template" />
                            </div>
                            <div>
                                <a href="{{ route('budget.download-workorder-template') }}" class="btn btn-primary" download>
                                    <i class="fa fa-download mr-1"></i> @lang('app.downloadExcelTemplate')
                                </a>
                            </div>
                        </div>
                        <small class="form-text text-muted" style="float: right">
                            @lang('messages.downloadExcelTemplate')
                        </small>
                    </div>
                </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <div class="row" id="import_table">
        <div class="col-sm-12">
            <x-form class="c-inv-form" id="saveOrderForm">
                <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY START -->
                <div class="row px-lg-4 px-md-4 px-3 py-3">
                    <!-- ORDER NUMBER START -->


                    <div class="col-md-6 col-lg-2" hidden>
                        <div class="form-group c-inv-select mb-4">
                            <x-forms.label fieldId="order_type" :fieldLabel="__('app.bill_type')" fieldRequired="true">
                            </x-forms.label>
                            <div class="select-others height-35 rounded">
                                    <select class="form-control select-picker" name="type" id="project" data-live-search="true">
                                           <option value="project">Planedge</option>
                                    </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="project_id" :fieldLabel="__('app.project')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="project_id" id="project_id"
                                data-live-search="true">
                                <option value="">--</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <x-forms.label fieldId="subproject_id" :fieldLabel="__('app.subproject')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="subproject_id" id="subproject_id"
                                data-live-search="true">
                                <option value="">--</option>
                            </select>
                        </x-forms.input-group>
                    </div>
                    <input type="hidden" id="companyGroupId" name="company_group_id">
                    <input type="hidden" id="companyGroupName" name="companyGroupName">

                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="billing_company_id" :fieldLabel="__('app.billing_company')" fieldRequired="true"></x-forms.label>
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="billing_company_id" id="billing_company_id"
                                data-live-search="true">
                                <option value="">--</option>
                            </select>
                        </x-forms.input-group>
                    </div>



                    <input type="hidden" name="order_type" id="order_type" value="work_order">

                    <div class="col-md-6 col-lg-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group c-inv-select mb-4">
                                    <x-forms.label fieldId="category" :fieldLabel="__('app.category')" fieldRequired="true">
                                    </x-forms.label>
                                    <div class="select-others height-35 rounded">
                                            <select class="form-control select-picker" name="category" id="category" data-live-search="true">
                                                <option value="contractor">Contractor</option>
                                                    <option value="consultant">Consultant</option>
                                            </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group c-inv-select mb-4">
                                    <x-forms.label fieldId="categorytype" :fieldLabel="__('app.categorytype')" fieldRequired="true">
                                    </x-forms.label>
                                    <div class="select-others height-35 rounded">
                                            <select class="form-control select-picker" name="categorytype" id="categorytype" data-live-search="true">
                                                 
                                                <option value="material">Material + Labour</option>
                                                <option value="labour">Labour</option>
                                            </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6 col-lg-2" hidden>
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.orderNumber')  <span class="text-danger">*</span></label>
                            <div class="input-group">

                                <input type="hidden" name="prefix" value="{{ $purchaseSetting->purchase_order_prefix }}">

                                <input type="text" name="purchase_order_number" id="purchase_order_number"
                                    class="form-control height-35 f-15" required
                                    placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">Number</label>
                            <div class="input-group">
                                <input type="text" name="po_number" id="po_number"
                                    class="form-control height-35 f-15" readonly
                                    placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                            </div>
                        </div>
                    </div>

                     <div class="col-md-6 col-lg-2">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.reference_number')</label>
                            <div class="input-group">
                                <input type="text" name="reference_number" id="reference_number"
                                    class="form-control height-35 f-15"
                                    placeholder="@lang('app.reference_number')"  aria-describedby="basic-addon2">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="form-group c-inv-select mb-4">
                            <x-forms.label fieldId="vendor_id" :fieldLabel="__('app.select') . ' ' . __('purchase::app.menu.vendor')" fieldRequired="true">
                            </x-forms.label>

                            <div class="select-others height-35 rounded">
                                @if ($vendorID)
                                    <input type="hidden" name="vendor_id" value="{{ $vendorID }}">
                                @endif
                                <select @if ($vendorID) disabled @endif
                                    class="form-control select-picker" name="vendor_id" id="vendor_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($vendors as $vendor)
                                        <option @if ($vendorID == $vendor->id) selected @endif
                                            value="{{ $vendor->id }}">{{ $vendor->primary_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- CURRENCY START -->
                    <div class="col-md-6 col-lg-3" hidden >
                        <div class="form-group c-inv-select mb-lg-0 mb-4">
                            <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                            </x-forms.label>

                            <div class="select-others height-35 rounded" id="select_currency_id">
                                <select class="form-control select-picker" name="currency_id" id="currency_id">
                                    @if (!is_null($purchaseVendorID))
                                        <option value="{{ $purchaseVendorID->currency->id }}">
                                            {{ $purchaseVendorID->currency->currency_code }}
                                            ({{ $purchaseVendorID->currency->currency_symbol }})</option>
                                    @else
                                        <option value="">--</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- CURRENCY END -->
                    <div class="col-md-6 col-lg-3" hidden>
                        <x-forms.label fieldId="exchange_rate" :fieldLabel="__('modules.currencySettings.exchangeRate')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="exchange_rate" name="exchange_rate"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            value="{{ $companyCurrency->exchange_rate }}" readonly>
                        <small id="currency_exchange" class="form-text text-muted">(
                            {{ company()->currency->currency_code }} @lang('app.to') <span
                                id="currency">{{ $companyCurrency->currency_code }}</span> )</small>
                    </div>
                    <div class="col-md-6 col-lg-3 ">
                        <div class="form-group mb-4">
                            <x-forms.label fieldId="purchase_date" :fieldLabel="__('purchase::modules.purchaseOrder.orderDate')" fieldRequired="true">
                            </x-forms.label>
                            <div class="input-group">
                                <input type="text" id="purchase_date" name="purchase_date"
                                    class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                                    placeholder="@lang('placeholders.date')"
                                    value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 ">
                        <div class="form-group mb-4">
                            <x-forms.label fieldId="expected_date" :fieldLabel="__('purchase::modules.purchaseOrder.expectedDeliveryDate')" fieldRequired="true">
                            </x-forms.label>
                            <div class="input-group">
                                <input type="text" id="expected_date" name="expected_date"
                                    class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                                    placeholder="@lang('placeholders.date')"
                                    value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="minbilllimit" :fieldLabel="__('modules.budget.minbilllimit')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="minbilllimit" name="minbilllimit"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="billingcycle" :fieldLabel="__('modules.budget.billingcycle')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="billingcycle" name="billingcycle"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="billingDuration" :fieldLabel="__('modules.budget.billingDuration')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="billingDuration" name="billingDuration"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-12">
                        <x-forms.file :fieldLabel="__('modules.import.file_boq')" fieldName="import_file" fieldRequired="true" fieldId="budget_import" />
                    </div>
                    <div class="col-md-12" hidden>
                        <x-forms.toggle-switch class="mr-0 mr-lg-12" :fieldLabel="__('modules.import.containsHeadings')" fieldName="heading"
                            fieldId="heading" />
                    </div>
                </div>
                <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

                <hr class="m-0 border-top-grey">

                <!-- CANCEL SAVE SEND START -->
                <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex">
                    <div class="d-flex mb-3 mb-lg-0 mb-md-0">

                        <div class="inv-action dropup mr-3">
                            <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                @lang('app.save')
                                <span><i class="fa fa-chevron-up f-15 text-white"></i></span>
                            </button>
                            <!-- DROPDOWN - INFORMATION -->
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                                <li>
                                    <a class="dropdown-item f-14 text-dark save-form" href="javascript:;"
                                        data-type="save">
                                        <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                                    </a>
                                </li>
                                <!--<li>-->
                                <!--    <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"-->
                                <!--        data-type="send">-->
                                <!--        <i class="fa fa-paper-plane f-w-500  mr-2 f-12"></i> @lang('app.saveSend')-->
                                <!--    </a>-->
                                <!--</li>-->
                                <!--<li>-->
                                <!--    <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"-->
                                <!--        data-type="mark_as_send" data-toggle="tooltip"-->
                                <!--        data-original-title="@lang('messages.markSentInfo')">-->
                                <!--        <i class="fa fa-check-double f-w-500  mr-2 f-12"></i> @lang('app.saveMark')-->
                                <!--    </a>-->
                                <!--</li>-->
                            </ul>
                        </div>

                        <!--<x-forms.button-secondary data-type="draft" class="save-form mr-3">@lang('app.saveDraft')-->
                        <!--</x-forms.button-secondary>-->

                    </div>

                    <x-forms.button-cancel :link="route('invoices.index')" class="border-0 ">@lang('app.cancel')
                    </x-forms.button-cancel>

                </x-form-actions>
                <!-- CANCEL SAVE SEND END -->

            </x-form>
            <!-- FORM END -->
        </div>
    </div>
</div>
<!-- CREATE INVOICE END -->

<script src="{{ asset('vendor/jquery/dropzone.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.save-form').click(function() {

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            $.easyAjax({
                url: "{{ route('purchase-order.store') }}",
                container: '#saveOrderForm',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true, // Commented so that we dot get error of Input variables exceeded 1000
                data: $('#saveOrderForm').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        $('#import_table').html(response.view);
                    }
                }
            })
        });

    });

    function ucWord(str) {
        str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });
        return str;
    }

    function checkboxChange(parentClass, id) {
        var checkedData = '';
        $('.' + parentClass).find("input[type= 'checkbox']:checked").each(function() {
            checkedData = (checkedData !== '') ? checkedData + ', ' + $(this).val() : $(this).val();
        });
        $('#' + id).val(checkedData);
    }

    $('#currency_id').change(function() {
        var curId = $(this).val();
        var companyCurrencyName = "{{ $companyCurrency->currency_code }}";
        var currentCurrencyName = $('#currency_id option:selected').attr('data-currency-code');
        var companyCurrency = '{{ $companyCurrency->id }}';

        if (curId == companyCurrency) {
            $('#exchange_rate').prop('readonly', true);
        } else {
            $('#exchange_rate').prop('readonly', false);
        }
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('payments.account_list') }}",
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            data: {
                'curId': curId,
                _token: token
            },
            success: function(response) {
                if (response.status == 'success') {
                    $('#bank_account_id').html(response.data);
                    $('#bank_account_id').selectpicker('refresh');
                    $('#exchange_rate').val(response.exchangeRate);
                    $('#currency_exchange').html('( ' + companyCurrencyName +
                        ' @lang('app.to') ' + currentCurrencyName + ' )');
                }
            }
        });
    });

    var vendorId = $('#vendor_id').val();
    if (vendorId) {
        vendorProduct(vendorId);
    }
    function vendorProduct(vendorId) {
        var companyCurrency = "{{ $companyCurrency->exchange_rate }}"
        var companyCurrencyCode = "{{ $companyCurrency->currency_code }}"
        if (vendorId) {
            $('#products').removeClass('d-none');
        } else {
            $('#products').addClass('d-none');
        }
        var url = "{{ route('purchase_order.vendor_currency') }}" + "?id=" + vendorId;

        $.easyAjax({
            url: url,
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            success: function(response) {
                if (response.data == null) {
                    $('#currency_id').html('<option>' + ' -- ' + '</option>');
                    $('#currency_id').selectpicker('refresh');
                    $('#exchange_rate').val(companyCurrency);
                    $('#exchange_rate').selectpicker('refresh');
                    $('#currency').html('<span>' + companyCurrencyCode + '</span>');
                    $('#currency').selectpicker('refresh');
                } else {
                    $('#currency_id').html('<option value="' + response.data.id + '">' + response.data
                        .currency_code + ' (' + response.data.currency_symbol + ')' + '</option>');
                    $('#currency_id').selectpicker('refresh');
                    $('#currency').html('<span>' + response.data.currency_code + '</span>')
                    $('#exchange_rate').val(response.data.exchange_rate);
                    $('#exchange_rate').selectpicker('refresh');
                }
            }
        });
    }

    $('#vendor_id').change(function() {
        var vendorId = $(this).val();
        vendorProduct(vendorId);
    });

  $(document).ready(function () {
    const $projectSelect = $('#project_id');
    const $subprojectSelect = $('#subproject_id');
    const defaultOption = '<option value="" disabled selected>-Select Sub Project-</option>';

    function loadSubprojects(projectId) {
        if (projectId) {
            $.ajax({
                url: "{{ route('get.subProjects') }}",
                type: "GET",
                data: {
                    project_id: projectId
                },
                success: function (response) {

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

                    // Set and hide group company
                    $('#companyGroupId').val(response.getClient.id);
                    $('#companyGroupName').val(response.getClient.company_name);

                    $('.select-picker').selectpicker('refresh');
                },
                error: function (xhr, status, error) {
                    console.error('Error:', status, error);
                    $('#subproject_id').html('<option value="">Error loading sub-projects</option>');
                    $('#billing_company_id').html('<option value="">Error loading billing companies</option>');
                    $('.select-picker').selectpicker('refresh');
                }
            });
        } else {
            $('#subproject_id').html('<option value="">Select a Project First</option>');
            $('#billing_company_id').html('<option value="">Select a Project First</option>');
            $('.select-picker').selectpicker('refresh');
        }
     }
    const initialProjectId = $projectSelect.val();
    if (initialProjectId) {
        loadSubprojects(initialProjectId);
    }

    $projectSelect.on('change', function () {
        const projectId = $(this).val();
        $subprojectSelect.empty().append(defaultOption);
        loadSubprojects(projectId);
    });
});


$(document).ready(function () {
    // Project & Subproject change
    $('#subproject_id, #project_id').on('change', function () {
        const subprojectId = $('#subproject_id').val();
        const projectId = $('#project_id').val();

        getPurchaseId();

        $.ajax({
            url: "{{ route('projects.checkBHforproject') }}",
            type: 'GET',
            data: {
                project_id: projectId,
                subproject_id: subprojectId
            },
            success: function (response) {
                if (response.status === 'error') {
                    alert(response.message || 'Invalid Project.');
                    $('#project_id').val('').selectpicker('refresh');
                    $('#subproject_id')
                        .empty()
                        .append('<option value="">Select Subproject</option>')
                        .selectpicker('refresh');
                } else if (response.status === 'error1') {
                    alert(response.message);
                    $('#subproject_id').val('').selectpicker('refresh');
                }
            },
            error: function (xhr) {
                console.error('Error in checkBHforproject:', xhr.responseText);
            }
        });
    });

    // Category & Category Type change
    $('#category, #categorytype').on('change', function () {
        getPurchaseId();
    });

    // Clear PO fields when relevant dropdowns change
    ['project', 'project_id', 'category', 'order_type', 'categorytype'].forEach(function (id) {
        $('#' + id).on('change', function () {
            $('#po_number').val('');
            $('#purchase_order_number').val('');
        });
    });

    // Trigger getPurchaseId when PO number input loses focus
    $('#purchase_order_number').on('blur', function () {
        getPurchaseId();
    });
});

// Function to fetch purchase ID
function getPurchaseId() {
    const billType = $('#project').val();
    const project = $('#project_id').val();
    const category = $('#category').val();
    const prefix = $('input[name="prefix"]').val();
    const purchaseOrderNumber = $('#purchase_order_number').val();
    const orderType = $('#order_type').val();
    const subproject_id = $('#subproject_id').val();
    const categorytype = $('#categorytype').val();

    if (!billType || !project || !category || !prefix || !subproject_id || !categorytype) {
        return;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('check_po_number') }}",
        type: 'GET',
        data: {
            bill_type: billType,
            project_id: project,
            category: category,
            prefix: prefix,
            order_type: orderType,
            subproject_id: subproject_id,
            categorytype: categorytype,
            _token: csrfToken
        },
        success: function (response) {
            if (response.exists) {
                alert("This PO Number already exists!");
                $('#purchase_order_number').val('');
                $('#po_number').val('');
            } else {
                $('#purchase_order_number').val(response.purchase_order_number);
                $('#po_number').val(response.po_number);
            }
        },
        error: function (xhr) {
            console.error("Error:", xhr.responseText);
        }
    });
}


$(document).ready(function () {
    $('#company_group_id').on('change', function () {
        var companyGroupId = $(this).val();

        // Clear previous options
        $('#billing_company_id').html('<option value="">--</option>');

        if (companyGroupId) {
            $.ajax({
                url: '{{ route("get-billing-companies") }}', // backend route
                type: 'GET',
                data: { company_group_id: companyGroupId },
                success: function (response) {
                    $.each(response, function (key, value) {
                        $('#billing_company_id').append('<option value="' + value.id + '">' + value.billing_company_name + '</option>');
                    });

                    // Refresh select-picker if you are using Bootstrap select
                    $('.select-picker').selectpicker('refresh');
                }
            });
        }
    });
});

</script>
