@php
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv" >
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.purchaseOrder')</h4>
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
            <div class="col-md-6 col-lg-4">
                <div class="form-group mb-4">
                    <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.orderNumber')</label>
                    <div class="input-group">
                        <div class="input-group-prepend  height-35 ">
                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                id="basic-addon1">{{ $purchaseSetting->purchase_order_prefix }}{{ $purchaseSetting->purchase_order_number_separator }}{{ $zero }}</span>
                        </div>
                        <input type="text" name="purchase_order_number" id="purchase_order_number"
                            class="form-control height-35 f-15"
                            value="@if (is_null($lastOrder)) 1 @else{{ $lastOrder }} @endif"
                            placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                    </div>
                </div>
            </div>
            <!-- ORDER NUMBER END -->
           {{-- {{dd(projects)}} --}}
           <div class="col-md-6 col-lg-4">
            <x-forms.label fieldId="project_id" :fieldLabel="__('app.project')" fieldRequired="true"></x-forms.label>
            <x-forms.input-group>
                <select class="form-control select-picker" name="project_id" id="project_id" data-live-search="true">
                    <option value="">--</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                    @endforeach
                </select>
            </x-forms.input-group>
        </div>

        <div class="col-md-6 col-lg-4">
            <x-forms.label fieldId="subproject_id" :fieldLabel="__('app.subproject')" fieldRequired="true"></x-forms.label>
            <x-forms.input-group>
                <select class="form-control select-picker" name="subproject_id" id="subproject_id" data-live-search="true">
                    <option value="">--</option>
                </select>
            </x-forms.input-group>
        </div>

        <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="order_type" :fieldLabel="__('app.select').' '.__('purchase::app.menu.order_type')" fieldRequired="true">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" name="order_type" id="order_type" data-live-search="true">
                                    <option value="po">Purchase Order</option>
                                    <option value="work_order">Work Order</option>
                            </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="vendor_id" :fieldLabel="__('app.select').' '.__('purchase::app.menu.vendor')" fieldRequired="true">
                    </x-forms.label>

                    <div class="select-others height-35 rounded">
                        @if($vendorID) <input type="hidden" name="vendor_id" value="{{$vendorID}}"> @endif
                            <select @if($vendorID) disabled @endif class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true">
                                <option value="">--</option>
                                @foreach ($vendors as $vendor)
                                    <option  @if($vendorID == $vendor->id) selected @endif value="{{ $vendor->id }}">{{ $vendor->primary_name }}</option>
                                @endforeach
                            </select>
                    </div>
                </div>
            </div>

            <!-- CURRENCY START -->
            <div class="col-md-6 col-lg-4" hidden>
                <div class="form-group c-inv-select mb-lg-0 mb-4">
                    <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                    </x-forms.label>

                    <div class="select-others height-35 rounded" id="select_currency_id">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @if (!is_null($purchaseVendorID))
                            <option value="{{ $purchaseVendorID->currency->id }}">{{ $purchaseVendorID->currency->currency_code }} ({{ $purchaseVendorID->currency->currency_symbol }})</option>
                            @else
                            <option value="">--</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
            <!-- CURRENCY END -->
            <div class="col-md-6 col-lg-4" hidden>
                <x-forms.label fieldId="exchange_rate" :fieldLabel="__('modules.currencySettings.exchangeRate')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="exchange_rate" name="exchange_rate"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" value="{{ $companyCurrency->exchange_rate}}" readonly>
                <small id="currency_exchange" class="form-text text-muted">( {{company()->currency->currency_code}} @lang('app.to') <span id="currency">{{ $companyCurrency->currency_code }}</span> )</small>
            </div>

            <div class="col-md-6 col-lg-4 ">
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

            <div class="col-md-6 col-lg-4 mt-3">
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

            <div class="col-md-6 col-lg-4 mt-3">
                <x-forms.label fieldId="minbilllimit" :fieldLabel="__('modules.budget.minbilllimit')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="minbilllimit" name="minbilllimit"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" >
            </div>
            <div class="col-md-6 col-lg-4 mt-3">
                <x-forms.label fieldId="billingcycle" :fieldLabel="__('modules.budget.billingcycle')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="billingcycle" name="billingcycle"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" >
            </div>
            <div class="col-md-6 col-lg-4 mt-3">
                <x-forms.label fieldId="billingDuration" :fieldLabel="__('modules.budget.billingDuration')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="billingDuration" name="billingDuration"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" >
            </div>
            <div class="col-md-6 col-lg-4 mt-3">
                <x-forms.label fieldId="reserveAmt" :fieldLabel="__('modules.budget.reserveAmt')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="reserveAmt" name="reserveAmt"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" >
            </div>


            <div class="col-md-12">
                <x-forms.file :fieldLabel="__('modules.import.file_boq')" fieldName="import_file" fieldId="budget_import" />
            </div>
            <div class="col-md-12">
                <x-forms.toggle-switch class="mr-0 mr-lg-12"
                    :fieldLabel="__('modules.import.containsHeadings')"
                    fieldName="heading"
                    fieldId="heading"/>
            </div>

        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <hr class="m-0 border-top-grey">



        <!--  ADD ITEM START-->
        {{-- <div class="row px-lg-4 px-md-4 px-3 pb-3 pt-0 mb-3  mt-2 ">
            <div class="col-md-12">
                <a class="f-15 f-w-500" href="javascript:;" id="add-item"><i
                        class="icons icon-plus font-weight-bold mr-1"></i>@lang('modules.invoices.addItem')</a>
            </div>
        </div> --}}
        <!--  ADD ITEM END-->

        <hr class="m-0 border-top-grey">

        <!-- NOTE AND TERMS AND CONDITIONS END -->

        <!-- UPLOAD MULTIPLE FILES START -->

        <!-- UPLOAD MULTIPLE FILES END -->

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
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="save">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="send">
                                <i class="fa fa-paper-plane f-w-500  mr-2 f-12"></i> @lang('app.saveSend')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="mark_as_send" data-toggle="tooltip" data-original-title="@lang('messages.markSentInfo')">
                                <i class="fa fa-check-double f-w-500  mr-2 f-12"></i> @lang('app.saveMark')
                            </a>
                        </li>
                    </ul>
                </div>

                <x-forms.button-secondary data-type="draft" class="save-form mr-3">@lang('app.saveDraft')
                </x-forms.button-secondary>

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

            // calculateTotal();

            // var discount = $('#discount_amount').html();
            // var total = $('.sub-total-field').val();

            // if (parseFloat(discount) > parseFloat(total)) {
            //     Swal.fire({
            //         icon: 'error',
            //         text: "{{ __('messages.discountExceed') }}",

            //         customClass: {
            //             confirmButton: 'btn btn-primary',
            //         },
            //         showClass: {
            //             popup: 'swal2-noanimation',
            //             backdrop: 'swal2-noanimation'
            //         },
            //         buttonsStyling: false
            //     });
            //     return false;
            // }

            $.easyAjax({
                url: "{{ route('purchase-order.store') }}",
                container: '#saveOrderForm',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true,  // Commented so that we dot get error of Input variables exceeded 1000
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

    var vendorId = $('#vendor_id').val();
    if (vendorId) {
        vendorProduct(vendorId);
    }

    function vendorProduct(vendorId)
    {
        var companyCurrency = "{{ $companyCurrency->exchange_rate }}"
        var companyCurrencyCode = "{{ $companyCurrency->currency_code }}"
        if(vendorId)
        {
            $('#products').removeClass('d-none');
        }
        else
        {
            $('#products').addClass('d-none');
        }
        var url = "{{route('purchase_order.vendor_currency')}}" + "?id=" + vendorId;

        $.easyAjax({
            url: url,
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            success: function(response) {
                if(response.data == null){
                    $('#currency_id').html('<option>'+' -- ' +'</option>');
                    $('#currency_id').selectpicker('refresh');
                    $('#exchange_rate').val(companyCurrency);
                    $('#exchange_rate').selectpicker('refresh');
                    $('#currency').html('<span>' + companyCurrencyCode + '</span>');
                    $('#currency').selectpicker('refresh');
                }
                else
                {
                    $('#currency_id').html('<option value="'+response.data.id+'">'+response.data.currency_code+ ' ('+response.data.currency_symbol+')'+'</option>');
                        $('#currency_id').selectpicker('refresh');
                        $('#currency').html('<span>' + response.data.currency_code+ '</span>')
                        $('#exchange_rate').val(response.data.exchange_rate);
                        $('#exchange_rate').selectpicker('refresh');
                }
            }
        });
    }

    $('#vendor_id').change(function(){
        var vendorId = $(this).val();
        vendorProduct(vendorId);
    });

    $(document).ready(function() {
    const initialProjectId = $('#project_id').val();
    if (initialProjectId) {
        loadSubprojects(initialProjectId);
    }

    $('#project_id').on('change', function() {
        const projectId = $(this).val();
        loadSubprojects(projectId);
    });

    function loadSubprojects(projectId) {
        if (projectId) {
            $.ajax({
                url: "{{ route('projects.getSubprojects') }}",
                type: 'GET',
                data: { project_id: projectId },
                success: function(response) {
                    const $subprojectSelect = $('#subproject_id');
                    $subprojectSelect.empty().append('<option value="">--</option>');
                    if (response.subprojects && response.subprojects.length > 0) {
                        $.each(response.subprojects, function(index, subproject) {
                            $subprojectSelect.append(`
                                <option data-content="<strong>${subproject.task_short_code || ''}</strong> ${subproject.heading || ''}"
                                        value="${subproject.id}">
                                    ${subproject.heading || 'No Heading'}
                                </option>
                            `);
                        });
                        $('.select-picker').selectpicker('refresh');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching subprojects:', xhr.responseText);
                }
            });
        } else {
            $('#subproject_id').empty().append('<option value="">--</option>');
            $('.select-picker').selectpicker('refresh');
        }
    }
});


</script>
