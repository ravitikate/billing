@php
    $addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}"> 

    <!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.purchaseOrder')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="saveOrderForm">
           @method('PUT')
         <div class="row px-lg-4 px-md-4 px-3 py-3">
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
                        <x-forms.label fieldId="company_group_id" :fieldLabel="__('app.group_company')" fieldRequired="true"></x-forms.label>
                        @php
                            $selectedCompany = old('company_group_id', $order->company_group_id ?? null);
                        @endphp
                    
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="company_group_id" id="company_group_id" data-live-search="true">
                                <option value="">--</option>
                                @foreach ($company as $companyItem)
                                    <option value="{{ $companyItem->id }}" {{ $selectedCompany == $companyItem->id ? 'selected' : '' }}>
                                        {{ $companyItem->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-6 col-lg-3">
    <x-forms.label fieldId="billing_company_id" :fieldLabel="__('app.billing_company')" fieldRequired="true"></x-forms.label>
    @php
        $selectedBillingCompany = old('billing_company_id', $order->billing_company_id ?? null);
    @endphp
    <x-forms.input-group>
        <select class="form-control select-picker" name="billing_company_id" id="billing_company_id" data-live-search="true">
            <option value="">--</option>
            @foreach ($billingCompanies as $billingCompany)
                <option value="{{ $billingCompany->id }}" {{ $selectedBillingCompany == $billingCompany->id ? 'selected' : '' }}>
                    {{ $billingCompany->billing_company_name }}
                </option>
            @endforeach
        </select>
    </x-forms.input-group>
</div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="project_id" :fieldLabel="__('app.project')" fieldRequired="true" />
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="project_id" id="project_id" data-live-search="true">
                                <option value="">--</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" {{ $order->project_id == $project->id ? 'selected' : '' }}>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="subproject_id" :fieldLabel="__('app.subproject')" fieldRequired="true" />
                         @php
                            $selectedSubProject = old('subproject_id', $order->subproject_id ?? null);
                        @endphp
                        <x-forms.input-group>
                            <select class="form-control select-picker" name="subproject_id" id="subproject_id" data-live-search="true">
                                <option value="">--</option>
                                 @foreach ($subProject as $task)
                                    <option value="{{ $task->id }}" {{ $selectedSubProject == $task->id ? 'selected' : '' }}>
                                        {{ $task->heading }} 
                                    </option>
                                @endforeach
                    
                            </select>
                        </x-forms.input-group>
</div>
                    <input type="hidden" name="order_type" id="order_type" value="purchase_order">
                    <div class="col-md-6 col-lg-3" hidden>
                        <div class="form-group c-inv-select mb-4">
                            <x-forms.label fieldId="category" :fieldLabel="__('app.category')" fieldRequired="true">
                            </x-forms.label>
                            <div class="select-others height-35 rounded">
                                    <select class="form-control select-picker" name="category" id="category" data-live-search="true">
                                            <option value="supplier">Supplier</option>
                                    </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2" hidden>
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.orderNumber')  <span class="text-danger">*</span></label>
                            <div class="input-group">

                                <input type="hidden" name="prefix" value="{{ $purchaseSetting->purchase_order_prefix }}">

                                <input type="text" name="purchase_order_number" id="purchase_order_number"
                                    class="form-control height-35 f-15" required    value="{{$order->purchase_order_number}}"
                                    placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="po_wo_id" value="{{$po_wo_id}}">
                    <div class="col-md-6 col-lg-3 mt-3">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">Number</label>
                            <div class="input-group">
                                <input type="text" name="po_number" id="po_number"
                                    class="form-control height-35 f-15" readonly   value="{{$order->po_number}}"
                                    placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mt-3">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.reference_number')</label>
                            <div class="input-group">
                                <input type="text" name="reference_number" id="reference_number"
                                    class="form-control height-35 f-15"  value="{{$order->reference_number}}"
                                    placeholder="@lang('app.reference_number')"  aria-describedby="basic-addon2">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mt-3">
                        <div class="form-group c-inv-select mb-4">
                            <x-forms.label fieldId="vendor_id" :fieldLabel="__('app.select') . ' ' . __('purchase::app.menu.vendor')" fieldRequired="true" />
                    
                            <div class="select-others height-35 rounded">
                                @php
                                    // Either use old input (form resubmission) or preloaded order data
                                    $selectedVendor = old('vendor_id', $order->vendor_id ?? $vendorID ?? '');
                                @endphp
                    
                                @if ($selectedVendor)
                                    <input type="hidden" name="vendor_id" value="{{ $selectedVendor }}">
                                @endif
                    
                                <select name="vendor_id" id="vendor_id"
                                        class="form-control select-picker"
                                        data-live-search="true"
                                        @if ($selectedVendor) disabled @endif>
                                    <option value="">--</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ $selectedVendor == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->primary_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
     </div>
                    </div> 
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
                    <div class="col-md-6 col-lg-3 mt-3">
                        <div class="form-group mb-4">
                            <x-forms.label fieldId="purchase_date" :fieldLabel="__('purchase::modules.purchaseOrder.orderDate')" fieldRequired="true">
                            </x-forms.label>
                            <div class="input-group">
                                <input type="text" id="purchase_date" name="purchase_date"
                                    class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                                    placeholder="@lang('placeholders.date')"
                                    value="{{ $order->purchase_date->translatedFormat(company()->date_format) }}">
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
                                    value="{{ $order->expected_delivery_date->translatedFormat(company()->date_format)  }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="minbilllimit" :fieldLabel="__('modules.budget.minbilllimit')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="minbilllimit" name="minbilllimit" value="{{$order->minimum_billing_limit}}"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="billingcycle" :fieldLabel="__('modules.budget.billingcycle')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="billingcycle" name="billingcycle" value="{{$order->billing_cycle}}"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <x-forms.label fieldId="billingDuration" :fieldLabel="__('modules.budget.billingDuration')" fieldRequired="true">
                        </x-forms.label>
                        <input type="number" id="billingDuration" name="billingDuration" value="{{$order->billing_duration}}"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15">
                    </div>
                    <div class="col-md-12">
                        <x-forms.file :fieldLabel="__('modules.import.file_boq')" fieldName="import_file" fieldRequired="true" fieldId="budget_import" />
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12" :fieldLabel="__('modules.import.containsHeadings')" fieldName="heading"
                            fieldId="heading" />
                    </div>
                </div>
        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns"> 
            <div class="d-flex"> 
                <div class="inv-action dropup mr-3">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="fa fa-chevron-up f-15 text-white"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="save">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i>
                                
                                    @lang('app.save')
                                
                            </a>
                        </li> 
                    </ul>
                </div>

                <x-forms.button-cancel :link="route('purchase-order.index')" class="border-0">@lang('app.cancel')
                </x-forms.button-cancel>
            </div>


        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<!-- CREATE INVOICE END -->

<script src="{{ asset('vendor/jquery/dropzone.min.js') }}"></script>
<script>
    $(document).ready(function() {

        $('.toggle-product-category').click(function() {
            $('.product-category-filter').toggleClass('d-none');
        });

         
         $('.save-form').click(function() {
var type = $(this).data('type');
            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            } 

            $.easyAjax({
                 url: "{{ route('purchase-order.update', $order->id) }}" + "?type=" + type,
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
        
 

         
        init(RIGHT_MODAL);

    });

    function checkboxChange(parentClass, id){
        var checkedData = '';
        $('.'+parentClass).find("input[type= 'checkbox']:checked").each(function () {
            checkedData = (checkedData !== '') ? checkedData+', '+$(this).val() : $(this).val();
        });
        $('#'+id).val(checkedData);
    }

    $('#currency_id').change(function() {
        var curId = $(this).val();
        var companyCurrencyName = "{{$companyCurrency->currency_code}}";
        var currentCurrencyName = $('#currency_id option:selected').attr('data-currency-code');
        var companyCurrency = '{{ $companyCurrency->id }}';

        if(curId == companyCurrency){
            $('#exchange_rate').prop('readonly', true);
        } else{
            $('#exchange_rate').prop('readonly', false);
        }
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('payments.account_list') }}",
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            data: { 'curId' : curId , _token: token},
            success: function(response) {
                if (response.status == 'success') {
                    $('#bank_account_id').html(response.data);
                    $('#bank_account_id').selectpicker('refresh');
                    $('#exchange_rate').val(response.exchangeRate);
                    $('#currency_exchange').html('( '+companyCurrencyName+' @lang('app.to') '+currentCurrencyName+' )');
                }
            }
        });
    });

    $('#vendor_id').change(function(){
        var vendorId = $(this).val();
        var url = "{{route('purchase_order.vendor_currency')}}" + "?id=" + vendorId;
        $.easyAjax({
            url: url,
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            success: function(response) {
                $('#currency_id').html('<option value="'+response.data.id+'">'+response.data.currency_code+ ' ('+response.data.currency_symbol+')'+'</option>');
                $('#currency_id').selectpicker('refresh');
            }
        });
    });

 
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
     
    const $projectSelect = $('#project_id');
    const $subprojectSelect = $('#subproject_id');
    const defaultOption = '<option value="" disabled>-Select Sub Project-</option>';

    // Get the pre-selected subproject ID from server-side
    const selectedSubProjectId = "{{ old('subproject_id', $order->subproject_id ?? '') }}";

    function loadSubprojects(projectId) {
        if (projectId) {
            $.ajax({
                url: "{{ route('get.subProjects') }}",
                type: "GET",
                data: { project_id: projectId },
                success: function (response) {
                    console.log(response);
                    $subprojectSelect.empty().append(defaultOption);

                    if (Array.isArray(response) && response.length > 0) {
                        $.each(response, function (index, subproject) {
                            const isSelected = (subproject.id == selectedSubProjectId) ? 'selected' : '';
                            $subprojectSelect.append(`
                                <option value="${subproject.id}" ${isSelected}
                                        data-content="${subproject.heading || ''}">
                                    ${subproject.heading || 'No Heading'}
                                </option>
                            `);
                        });
                    } else {
                        $subprojectSelect.append('<option value="" disabled selected>No Sub Projects Found</option>');
                    }

                    $('.select-picker').selectpicker('refresh');
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching subprojects:', status, error);
                    $subprojectSelect.html('<option value="">Error loading sub-projects</option>');
                    $('.select-picker').selectpicker('refresh');
                }
            });
        } else {
            $subprojectSelect.html('<option value="">Select a Project First</option>');
            $('.select-picker').selectpicker('refresh');
        }
    }

    // Load subprojects on page load if a project and subproject are already selected
    const initialProjectId = $projectSelect.val();
    if (initialProjectId) {
        loadSubprojects(initialProjectId);
    }

    // Load subprojects on project change
    $projectSelect.on('change', function () {
        const projectId = $(this).val();
        // Clear current subproject selection
        $subprojectSelect.empty().append(defaultOption);
        // Reset selected subproject ID (because we're changing the project)
        loadSubprojects(projectId);
    });
    });
</script>
 
