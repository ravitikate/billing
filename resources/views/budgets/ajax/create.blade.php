@php
    $addProductPermission = user()->permission('add_product');
@endphp

<!-- for sortable content -->
<link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">
<style>
    #dummyField {
        position: relative;
    }

    #overflowBox {
        background: transparent;
        height: 208px;
        position: absolute;
        z-index: 1;
        width: 100%;
        cursor: pointer;
    }
</style>
@if (!in_array('clients', user_modules()))
    <x-alert class="mb-3" type="danger" icon="exclamation-circle"><span>@lang('messages.enableClientModule')</span>
        <x-forms.link-secondary icon="arrow-left" :link="route('estimates.index')">@lang('app.back')</x-forms.link-secondary>
    </x-alert>
@else
    <div class="bg-white rounded b-shadow-4 create-inv">
        <div class="px-lg-4 px-md-4 px-3 py-3">
            <h4 class="mb-0 f-21 font-weight-normal ">@lang('app.budgetDetails')</h4>
        </div>
        <hr class="m-0 border-top-grey">
        <x-form class="c-inv-form" id="saveInvoiceForm">

            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-md-6 col-lg-4">
                    <div class="form-group mb-4">
                        <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('modules.budget.budgetNumber')</label>
                        <div class="input-group">
                            <div class="input-group-prepend  height-35 ">
                                <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                    id="basic-addon1">
                                    {{ $budget_prefix }}
                                    {{ $invoiceSetting->estimate_number_separator }}{{ $zero }}</span>
                            </div>
                            <input type="text" name="budget_number" id="budget_number"
                                class="form-control height-35 f-15"
                                value="@if (is_null($lastBudget)) 1 @else{{ $lastBudget }} @endif"
                                placeholder="0019" aria-label="0019" aria-describedby="basic-addon1" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="form-group mb-4">
                        <x-forms.label fieldId="due_date" :fieldLabel="__('modules.estimates.validTill')" fieldRequired="true">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="text" id="valid_till" name="valid_till"
                                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                                placeholder="@lang('placeholders.date')"
                                value="{{ Carbon\Carbon::today()->addDays(30)->format(company()->date_format) }}">
                        </div>
                    </div>
                </div>
                <!-- INVOICE DATE END -->

                <!-- FREQUENCY START -->
                <div class="col-md-6 col-lg-4">
                    <div class="form-group c-inv-select mb-4">
                        <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                        </x-forms.label>

                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" name="currency_id" id="currency_id">
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">
                                        {{ $currency->currency_code . ' (' . $currency->currency_symbol . ')' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <!-- FREQUENCY END -->

                <!-- CLIENT START -->

                <div class="col-md-6 col-lg-4">

                    <div class="form-group c-inv-select mb-4">
                        <x-forms.label fieldId="project_id" :fieldLabel="__('app.project')"></x-forms.label>

                        <x-forms.input-group>
                            <select class="form-control select-picker" data-live-search="true" data-size="8"
                                name="project_id" id="project_id">
                                <option value="">--</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">
                                        {{ $project->project_name }}</option>
                                @endforeach
                            </select>



                            <x-forms.link-primary :link="route('projects.create')"
                                class="btn btn-outline-secondary border-grey openRightModal float-left ">
                                @lang('app.addProject')
                            </x-forms.link-primary>

                        </x-forms.input-group>



                    </div>
                </div>
                <!-- CLIENT END -->



                <div class="col-md-12 my-3">
                    <div class="form-group">
                        <x-forms.label fieldId="description" :fieldLabel="__('app.description')">
                        </x-forms.label>
                        <textarea class="f-14  w-100 form-control" name="budget_description" rows="4" placeholder="@lang('placeholders.invoices.description')"></textarea>
                    </div>
                </div>

            </div>
            <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

            <x-forms.custom-field :fields="$fields"></x-forms.custom-field>

            <hr class="m-0 border-top-grey">

            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-md-3  product-category-filter">
                    <div class="form-group c-inv-select mb-4">
                        <x-forms.input-group>
                            <select class="form-control select-picker" id="product_category_id" data-live-search="true">
                                {{-- <option value="">{{  __('app.menu.selectProductCategory')  }}</option> --}}
                                <option value="">{{ __('Select Budget Head') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->category_name }}</option>
                                @endforeach
                            </select>

                            <x-slot name="append">
                                <button id="add-category" type="button" data-toggle="tooltip"
                                    data-original-title="{{ __('app.add') . ' ' . __('modules.productCategory.productCategory') }}"
                                    class="btn btn-outline-secondary border-grey">@lang('app.add')</button>
                            </x-slot>

                        </x-forms.input-group>
                    </div>
                </div>

            </div>

            <div id="sortable">
                <div id="dummyField">
                    <div id="overflowBox"></div>
                    <div class="d-flex px-4 py-3 c-inv-desc item-row">
                        <div class="d-flex align-items-center">
                            <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                            <input type="hidden" name="sort_order[]" value="1">
                        </div>

                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                            <table width="100%">
                                <tbody>
                                    <tr class="text-dark-grey font-weight-bold f-14">
                                        <td width="{{ $invoiceSetting->hsn_sac_code_show ? '35%' : '40%' }}"
                                            class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td width="10%" class="border-0" align="right">@lang('app.hsnSac')</td>
                                        @endif
                                        <td width="10%" class="border-0" align="right">
                                            WBS
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.qty')
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.unitPrice')
                                        </td>
                                        <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')
                                        </td>
                                        <td width="17%" class="border-0 bblr-mbl" align="right">
                                            @lang('modules.invoices.amount')</td>
                                    </tr>
                                    <tr>
                                        <td class="border-bottom-0 btrr-mbl btlr">
                                            <input type="text" class="f-14 border-0 w-100 item_name form-control"
                                                name="item_name[]" placeholder="@lang('modules.expenses.itemName')">
                                        </td>
                                        <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                            <textarea class="form-control f-14 border-0 w-100 mobile-description" name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')"></textarea>
                                        </td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-bottom-0">
                                                <input type="text" min="1"
                                                    class="f-14 border-0 w-100 text-right hsn_sac_code form-control"
                                                    value="" name="hsn_sac_code[]">
                                            </td>
                                        @endif
                                        <td class="border-bottom-0">
                                            <input type="text" min="1"
                                                class="form-control f-14 border-0 w-100 text-right quantity mt-3"
                                                value="1" name="wbc[]">

                                            <input type="hidden" name="wbc[]" value="">
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="form-control f-14 border-0 w-100 text-right quantity mt-3"
                                                value="1" name="quantity[]">

                                            <input type="hidden" name="product_id[]" value="">
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                placeholder="0.00" value="0" name="cost_per_item[]">
                                        </td>
                                        <td class="border-bottom-0">
                                            <div class="select-others height-35 rounded border-0">
                                                <select id="multiselect" name="taxes[0][]"
                                                    class="select-picker type customSequence border-0" data-size="3">
                                                    <option selected disabled>--Select GST--</option>
                                                    @foreach ($taxes as $tax)
                                                        <option data-rate="{{ $tax->rate_percent }}"
                                                            data-tax-text="{{ $tax->tax_name . ':' . $tax->rate_percent }}%"
                                                            value="{{ $tax->id }}">{{ $tax->tax_name }}:
                                                            {{ $tax->rate_percent }}%</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td rowspan="2" align="right" valign="top"
                                            class="bg-amt-grey btrr-bbrr">
                                            <span class="amount-html">0.00</span>
                                            <input type="hidden" class="amount" name="amount[]" value="0">
                                        </td>
                                    </tr>
                                    <tr class="d-none d-md-table-row d-lg-table-row">
                                        <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                            class="dash-border-top bblr">
                                            <textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')"></textarea>
                                        </td>
                                        <td class="border-left-0">
                                            <input type="file" class="dropify" name="invoice_item_image[]"
                                                data-allowed-file-extensions="png jpg jpeg bmp" data-height="70" />
                                            <input type="hidden" name="invoice_item_image_url[]">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <a href="javascript:;"
                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="m-0 border-top-grey">
            <!-- TOTAL, DISCOUNT START -->
            <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
                <table width="100%" class="text-right f-14 ">
                    <tbody>
                        <tr>
                            <td width="50%" class="border-0 d-lg-table d-md-table d-none"></td>
                            <td width="50%" class="p-0 border-0 c-inv-total-right">
                                <table width="100%">
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="border-top-0 text-dark-grey">
                                                @lang('modules.invoices.subTotal')</td>
                                            <td width="30%" class="border-top-0 sub-total">0.00</td>
                                            <input type="hidden" class="sub-total-field" name="sub_total"
                                                value="0">
                                        </tr>
                                        <tr>
                                            <td width="20%" class="text-dark-grey">@lang('modules.invoices.discount')
                                            </td>
                                            <td width="40%" style="padding: 5px;">
                                                <table width="100%" class="mw-250">
                                                    <tbody>
                                                        <tr>
                                                            <td width="70%" class="c-inv-sub-padding">
                                                                <input type="number" min="0"
                                                                    name="discount_value"
                                                                    class="form-control f-14 border-0 w-100 text-right discount_value"
                                                                    placeholder="0"
                                                                    {{ isset($estimteTemplate) ? $estimateTemplate->discount : '0' }}
                                                                    </td>
                                                            <td width="30%" align="left"
                                                                class="c-inv-sub-padding">
                                                                <div
                                                                    class="select-others select-tax height-35 rounded border-0">
                                                                    <select class="form-control select-picker"
                                                                        id="discount_type" name="discount_type">
                                                                        <option @selected(isset($estimateTemplate) && $estimateTemplate->discount_type == 'percent')
                                                                            value="percent">%
                                                                        </option>
                                                                        <option @selected(isset($estimateTemplate) && $estimateTemplate->discount_type == 'fixed')
                                                                            value="fixed">
                                                                            @lang('modules.invoices.amount')</option>
                                                                    </select>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td><span id="discount_amount">
                                                    @if (isset($estimate))
                                                        {{ number_format((float) $estimate->discount, 2, '.', '') }}
                                                    @else
                                                        0.00
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>@lang('modules.invoices.tax')</td>
                                            <td colspan="2" class="p-0 border-0">
                                                <table width="100%" id="invoice-taxes">
                                                    <tr>
                                                        <td colspan="2"><span class="tax-percent">0.00</span></td>
                                                    </tr>
                                                </table>
                                            </td>

                                        </tr>
                                        <tr class="bg-amt-grey f-16 f-w-500">
                                            <td colspan="2">@lang('modules.invoices.total')</td>
                                            <td><span class="total">0.00</span></td>
                                            <input type="hidden" class="total-field" name="total" value="0">
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- TOTAL, DISCOUNT END -->



            <!-- CANCEL SAVE SEND START -->
            <x-form-actions class="c-inv-btns">
                <div class="d-flex mb-3"><a class="btn-primary f-14 text-dark save-form" href="javascript:;"
                        data-type="save">
                        <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                    </a>
                </div>

            </x-form-actions>
            <!-- CANCEL SAVE SEND END -->

        </x-form>
        <!-- FORM END -->
    </div>
    <!-- CREATE INVOICE END -->

    <!-- for sortable content -->
    <script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>
    <script>
        $(function() {
            $("#sortable").sortable();
        });
        $('#overflowBox').on('click', function() {
            alert('First Select Budget Head');
        })
        $(document).ready(function() {
            $('#add-category').click(function() {

                const url = "{{ route('productCategory.create') }}";
                $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
                $.ajaxModal(MODAL_LG, url);
            })


            $('.custom-date-picker').each(function(ind, el) {
                datepicker(el, {
                    position: 'bl',
                    ...datepickerConfig
                });
            });

            const dp1 = datepicker('#valid_till', {
                position: 'bl',
                dateSelected: new Date("{{ str_replace('-', '/', today()->addDays(30)) }}"),
                ...datepickerConfig
            });


            $('#project_id').on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue) {
                var id = $(this).val();
                // alert(id);
                $.ajax({
                   url: "{{ route('budget.checkProject') }}",
                    type: "GET",
                    data: {
                        project_id: id,
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            alert(response.message);
                            $('#project_id').val('').selectpicker('refresh');
                        }
                    }
                })
            });
            $('#product_category_id').on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue) {
                $('#dummyField').hide();
                e.stopImmediatePropagation()
                var id = $(this).val();
                if (previousValue != id && id != '') {
                    addProduct(id);
                }
            });

            function addProduct(id) {
                var currencyId = $('#currency_id').val();

                $.easyAjax({
                    url: "{{ route('budget.add_item') }}",
                    type: "GET",
                    data: {
                        id: id,
                        currencyId: currencyId
                    },
                    blockUI: true,
                    success: function(res) {
                        if ($('input[name="item_name[]"]').val() == '') {
                            $("#sortable .item-row").remove();
                        }
                        console.log(res);

                        let html = `<div class="d-flex px-4 py-3 c-inv-desc item-row" data-id="${id}">
                                        <div class="d-flex align-items-center">
                                            <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                                            <input type="hidden" name="sort_order[]" value="1">
                                        </div>
                                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                                            <table width="100%">
                                                <tbody>
                                                          <tr class="text-dark-grey font-weight-bold f-14">
                                                            <td width="50%" class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                                            <td width="10%" class="border-0" align="right">
                                                                WBS
                                                            </td>
                                                            <td width="10%" class="border-0" align="right">
                                                                @lang('modules.invoices.unitPrice')
                                                            </td>
                                                            <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')
                                                            </td>
                                                            <td width="17%" class="border-0 bblr-mbl" align="right">
                                                                @lang('modules.invoices.amount')</td>
                                                        </tr>
                                                    <tr>
                                                        <td class="border-bottom-0 btrr-mbl btlr">
                                                            <input type="text" class="f-14 border-0 w-100 item_name form-control"
                                                                name="item_name[]" placeholder="@lang('placeholders.date')"
                                                                value="${res.data['category_name']}">
                                                            <input type="hidden" name="product_category_id[]"
                                                                value="${res.data['id']}" required>

                                                            <input type="hidden" min="1" max="1" class="quantity"
                                                                value="1" name="quantity[]" readonly>
                                                        </td>

                                                        <td class="border-bottom-0">
                                                            <input type="test"
                                                                class="f-14 border-0 w-100 text-right form-control"
                                                                placeholder="wbs"
                                                                name="wbs[]" required>
                                                        </td>

                                                        <td class="border-bottom-0">
                                                            <input type="number" min="1"
                                                                class="f-14 border-0 w-100 text-right cost_per_item form-control" min="1"
                                                                placeholder="0.00" value="0.00"
                                                                name="cost_per_item[]" required>
                                                        </td>
                                                        <td class="border-bottom-0">
                                                            <div class="select-others height-35 rounded border-0">
                                                                <select id="multiselect" name="taxes[0][]"
                                                                    class="form-control select-picker type customSequence border-0" data-size="3">
                                                                    <option selected disabled>--Select GST--</option>
                                                                    @foreach ($taxes as $tax)
                                                                        <option data-rate="{{ $tax->rate_percent }}"
                                                                            data-tax-text="{{ $tax->tax_name . ':' . $tax->rate_percent }}%"
                                                                            value="{{ $tax->id }}"
                                                                            >
                                                                            {{ $tax->tax_name }}: {{ $tax->rate_percent }}%
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                            </div>
                                                        </td>
                                                        <td rowspan="2" align="right" valign="top"
                                                            class="bg-amt-grey btrr-bbrr">
                                                            <span class="amount-html">0.00</span>
                                                            <input type="hidden" class="amount" name="amount[]"
                                                                value="0">
                                                        </td>
                                                    </tr>
                                                    <tr class="d-none d-md-table-row d-lg-table-row">
                                                        <td colspan="2" class="dash-border-top bblr">
                                                            <textarea class="w-100 desktop-description form-control" name="item_summary[]" rows="3"
                                                                placeholder="@lang('placeholders.invoices.description')">
                                                                </textarea>
                                                        </td>
                                                        <td class="border-left-0">
                                                            <input type="file" class="dropify" name="invoice_item_image[]"
                                                                data-allowed-file-extensions="png jpg jpeg bmp" data-height="70" />
                                                            <input type="hidden" name="invoice_item_image_url[]">
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <a href="javascript:;"
                                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                                                </div>
                                    </div>`;

                        // Check if a row with the same product ID already exists
                        let existingRow = $('#sortable').find(`.item-row[data-id="${id}"]`);

                        if (existingRow.length > 0) {
                            // Replace the existing row
                            // existingRow.replaceWith(html);
                            alert('already exists');
                        } else {
                            // Append if it doesn't exist
                            $('#sortable').append(html);
                        }
                    }
                });
            }



            $('#saveInvoiceForm').on('click', '.remove-item', function() {
                $(this).closest('.item-row').fadeOut(300, function() {
                    $(this).remove();
                    $('select.customSequence').each(function(index) {
                        $(this).attr('name', 'taxes[' + index + '][]');
                        $(this).attr('id', 'multiselect' + index + '');
                    });
                    calculateTotal();
                });
            });


            $('.save-form').click(function() {
                var type = $(this).data('type');

                // Calculate total before validation
                calculateTotal();



                var isValid = true;
                var projectId = $('#project_id').val();
    if (!projectId || projectId.trim() === '') {
        alert('Please select a project before saving.');
        return false;
    }
                // 1️⃣ Check if at least one "Budget Head" (Item Name) is added
                var hasItem = false;
                $('input[name="item_name[]"]').each(function() {
                    if ($(this).val().trim() !== '') {
                        hasItem = true;
                    }
                });

                if (!hasItem) {
                    alert('At least one Budget Head must be added.');
                    return false;
                }

                // 2️⃣ Check if any "Cost per Item" is empty or zero
                var hasValidCost = true;
                $('input[name="cost_per_item[]"]').each(function() {
                    if ($(this).val().trim() === '' || parseFloat($(this).val()) <= 0) {
                        hasValidCost = false;
                        return false; // Stop checking further
                    }
                });

                if (!hasValidCost) {
                    alert('Cost per item must be greater than zero.');
                    return false;
                }

                // 3️⃣ Check if at least one tax is selected
                var hasTax = true;
                $('select[name^="taxes"]').each(function() {
                    if ($(this).val() === null || $(this).val() === '') {
                        hasTax = false;
                        return false; // Stop checking further
                    }
                });

                if (!hasTax) {
                    alert('Please select a tax for all items.');
                    return false;
                }

                // 4️⃣ Check if the discount is greater than the total
                var discount = parseFloat($('#discount_amount').html());
                var total = parseFloat($('.sub-total-field').val());

                if (discount > total) {
                    Swal.fire({
                        icon: 'error',
                        text: "{{ __('messages.discountExceed') }}",
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        showClass: {
                            popup: 'swal2-noanimation',
                            backdrop: 'swal2-noanimation'
                        },
                        buttonsStyling: false
                    });
                    return false;
                }

                // ✅ If all validations pass, proceed with form submission
                $.easyAjax({
                    url: "{{ route('budget.store') }}?type=" + type,
                    container: '#saveInvoiceForm',
                    type: "POST",
                    disableButton: true,
                    blockUI: true,
                    buttonSelector: ".save-form",
                    redirect: true,
                    file: true,
                    data: $('#saveInvoiceForm').serialize(),
                    success: function(response) {
                        if (response.status == 'error') {
                            alert(response.message);
                        }
                        if (response.status == 'success') {
                            window.location.href = response.redirectUrl;
                        }
                    }
                });
            });


            $('#saveInvoiceForm').on('click', '.remove-item', function() {
                $(this).closest('.item-row').fadeOut(300, function() {
                    $(this).remove();
                    $('select.customSequence').each(function(index) {
                        $(this).attr('name', 'taxes[' + index + '][]');
                        $(this).attr('id', 'multiselect' + index + '');
                    });
                    calculateTotal();
                });
            });

            $('#saveInvoiceForm').on('keyup', '.quantity,.cost_per_item,.item_name, .discount_value', function() {
                var quantity = $(this).closest('.item-row').find('.quantity').val();
                var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
                var amount = (quantity * perItemCost);

                $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
                $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

                calculateTotal();
            });

            $('#saveInvoiceForm').on('change', '.type, #discount_type', function() {
                var quantity = $(this).closest('.item-row').find('.quantity').val();
                var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
                var amount = (quantity * perItemCost);

                $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
                $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

                calculateTotal();
            });

            $('#saveInvoiceForm').on('input', '.quantity', function() {
                var quantity = $(this).closest('.item-row').find('.quantity').val();
                var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
                var amount = (quantity * perItemCost);

                $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
                $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

                calculateTotal();
            });

            calculateTotal();

            init(RIGHT_MODAL);
        });

        function ucWord(str) {
            str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                return letter.toUpperCase();
            });
            return str;
        }
    </script>
@endif
