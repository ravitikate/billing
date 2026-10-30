<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<!-- CREATE BILL
     START -->
<div class="bg-white rounded b-shadow-4 create-inv">


    <!-- HEADING START -->
    <div class="px-3 py-3 px-lg-4 px-md-4">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.bill')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="save-bill-data-form">

        <!-- BILL NUMBER, DATE, FREQUENCY START -->
        <div class="px-3 pt-3 row px-lg-4 px-md-4">
            <!-- BILL NUMBER START -->
            <div class="col-md-4">
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="mb-12" fieldId="bill_number" :fieldLabel="__('purchase::app.menu.billNumber')" fieldRequired="true">
                    </x-forms.label>
                    <x-forms.input-group>
                        <x-slot name="prepend">
                            <span
                                class="input-group-text">{{ $purchaseSetting->bill_prefix }}{{ $purchaseSetting->bill_number_separator }}{{ $zero }}</span>
                        </x-slot>
                        <input type="number" name="bill_number" id="bill_number" class="form-control height-35 f-15"
                            value="{{ is_null($lastBill) ? 1 : $lastBill }}">
                    </x-forms.input-group>
                </div>
            </div>

            <!-- BILL NUMBER END -->
            <!-- SELECT VENDOR -->
            @if ($purchaseVendorsDetails)
                <input type="hidden" id="vendor-id" name="vendor_id" value="{{ $purchaseVendorsDetails->id }}">
            @else
                <div class="col-md-4">
                    <x-forms.label fieldId="vendor-id" :fieldLabel="__('purchase::app.selectVendor')">
                    </x-forms.label>
                    <div class="rounded select-others height-35">

                        @if ($vendorID)
                            <input type="hidden" name="vendor_id" value="{{ $vendorID }}">
                        @endif
                        <select @if ($vendorID) disabled @endif class="form-control select-picker"
                            id="vendor-id" name="vendor_id" data-live-search="true" data-size="8">
                            @if (isset($type) && $type == 'order')
                                <option value="{{ $purchaseOrder->vendor->id }}" selected>
                                    {{ $purchaseOrder->vendor->primary_name }}</option>
                            @elseif($purchaseVendors->isNotEmpty())
                                <option value="">--</option>
                                @foreach ($purchaseVendors as $purchaseVendor)
                                    <option @if ($vendorID == $purchaseVendor->id) selected @endif
                                        value="{{ $purchaseVendor->id }}">{{ $purchaseVendor->primary_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                </div>
            @endif
            <!-- SELECT VENDOR -->

            <!-- BILL DATE START -->
            <div class="col-md-4">
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label fieldId="due_date" :fieldLabel="__('purchase::app.menu.billDate')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="bill_date" name="issue_date"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
                    </div>
                </div>
            </div>
            <!-- BILL DATE END -->
            <div class="col-md-4">
                <div class="mb-4 form-group c-inv-select">
                    <x-forms.select fieldId="purchase-order" :fieldLabel="__('purchase::app.purchaseOrder.purchaseOrder')" fieldName="purchase_order_id"
                        search="true">
                        @if (isset($type) && $type == 'order')
                            <option option value="{{ $purchaseOrder->id }}" selected>
                                {{ $purchaseOrder->purchase_order_number }}</option>
                        @else
                            <option value="">--</option>
                        @endif
                    </x-forms.select>
                </div>
            </div>
            <div class="col-md-4 col-lg-4">
                <input type="text" id="contractValue">
                <x-forms.label class="mt-3" fieldId="purchase_item" :fieldLabel="__('modules.contracts.contractType')"
                    fieldRequired="true"></x-forms.label>
                <x-forms.input-group>
                    <select class="form-control select-picker" name="purchase_item" id="purchase_item"
                        data-live-search="true">
                        <option value="">--</option>

                    </select>


                </x-forms.input-group>
            </div>

        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->



        <div id="sortable">


        </div>

        <hr class="m-0 border-top-grey">
        <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
            <table width="100%" class="text-right f-14">
                <tbody>
                    <tr>
                        <td width="50%" class="border-0 d-lg-table d-md-table d-none"></td>
                        <td width="50%" class="p-0 border-0 c-inv-total-right">
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            Sub Total</td>
                                        <td width="30%" class="border-top-0 sub-total">0.00</td>
                                        <input type="hidden" class="sub-total-field" name="sub_total"
                                            value="0" autocomplete="off">
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            Contract Value</td>
                                        <td width="30%" class="border-top-0" id="contract_value">0.00
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            Remaining Amount</td>
                                        <td width="30%" class="border-top-0" id="remain_amt">0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- NOTE AND TERMS AND CONDITIONS START -->
        <div class="flex-wrap px-3 py-3 d-flex px-lg-4 px-md-4">
            <div class="p-0 mb-3 col-md-6 col-sm-12 c-inv-note-terms mb-lg-0 mb-md-0">
                <x-forms.label fieldId="" class="" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4" placeholder="@lang('placeholders.invoices.note')"></textarea>
            </div>
        </div>

        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex">
            <div class="mb-3 d-flex mb-lg-0 mb-md-0">

                <div class="mr-3 inv-action dropup">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="text-white fa fa-chevron-up f-15"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="draft">
                                <i class="mr-2 fa fa-save f-w-500 f-11"></i> @lang('app.saveDraft')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="open">
                                <i class="mr-2 fa fa-paper-plane f-w-500 f-12"></i> @lang('purchase::modules.purchaseBill.saveAsOpen')
                            </a>
                        </li>
                    </ul>
                </div>
            </div>


            <x-forms.button-cancel :link="isset($vendorID) ? route('vendors.show', $vendorID) . '?tab=bills' : route('bills.index')" class="border-0">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<script>
    $(document).ready(function() {

        var type = "{{ isset($type) ? $type : '' }}";

        if (type == 'order') {
            let url = "{{ route('purchase_order_products') }}";
            let purchaseOrderId = "{{ $purchaseOrder ? $purchaseOrder->id : '' }}";
            addProduct(purchaseOrderId, url);
        }

        let defaultImage = '';
        let lastIndex = 0;

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        const dp1 = datepicker('#bill_date', {
            position: 'bl',
            ...datepickerConfig
        });


        calculateTotal();
        var vendorId = "{{ $purchaseOrder ? $purchaseOrder->vendor->id : '' }}";

        function vendorOrders(vendorId) {
            let url = "{{ route('purchase_orders') }}";
            let prefix =
                "{{ $purchaseSetting->purchase_order_prefix }}{{ $purchaseSetting->purchase_order_number_seprator }}{{ $zero }}"
            $.easyAjax({
                url: url,
                type: "GET",
                data: {
                    vendor_id: vendorId
                },
                success: function(response) {
                    var options = [];
                    var rData = [];
                    rData = response.data;
                    var options = [];
                    var purchaseOrderId = $('#purchase-order').val();

                    $.each(rData, function(index, value) {
                        let selected = "";

                        if (purchaseOrderId == value.id) {
                            selected = "selected";
                        }

                        let purchaseOrderNumber = value.purchase_order_number ? value
                            .purchase_order_number : 'N/A';
                        let selectData = '<option ' + selected + ' value="' + value.id +
                            '">' + purchaseOrderNumber + '</option>';

                        options.push(selectData);
                    });

                    $('#purchase-order').html('<option value="">--</option>' + options.join(''));
                    $('#purchase-order').selectpicker('refresh');

                }

            });
        }

        $('#vendor-id').change(function() {
            let vendorId = $(this).val();
            vendorOrders(vendorId);
        });

        let vendor_id = $('#vendor-id').val();
        if (vendor_id) {
            vendorOrders(vendor_id);
        }


        $('#purchase-order').change(function() {
            let purchaseOrderId = $(this).val();
            // let url = "{{ route('purchase_order_products') }}";
            let url1 = "{{ route('purchase_order_products_list') }}";
            // $('#bill-desc').remove();
            // $('.c-inv-total').remove();
            // addProduct(purchaseOrderId, url);
            // $('#bill-desc').remove();
            // $('.c-inv-total').remove();
            purchaseOrderItem(purchaseOrderId, url1)


        });
        function purchaseOrderItem(purchaseOrderId, url) {
        console.log('Fetching data for PO:', purchaseOrderId);

        $.easyAjax({
            url: url,
            type: "GET",
            data: {
                purchase_order_id: purchaseOrderId
            },
            blockUI: true,
            success: function(res) {
                // console.log();
                $('#contractValue').val(res.data.purchaseOrder.total)
                if (res.status === 'success' && Array.isArray(res.data.purchaseItems)) {
                    let options =
                        '<option value="">{{ __('app.select') . ' ' . __('app.product') }}</option>';

                    res.data.purchaseItems.forEach(item => {
                        options += `<option value="${item.id}">${item.item_name}</option>`;
                    });

                    $('#purchase_item').html(options);
                    $('#purchase_item').selectpicker('refresh');
                } else {
                    alert('No items found.');
                }
            },

            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Error occurred while fetching purchase items.');
            }
        });
    }
    $('#purchase_item').change(function() {
            let purchaseProductId = $(this).val();
// alert(purchaseOrderId);
            let url = "{{ route('purchase_order_product_item') }}";
            addProduct(purchaseProductId, url);
        });






        function addProduct(purchaseProductId, url) {
            $.easyAjax({
                url: url,
                type: "GET",
                data: {
                    purchase_product_id: purchaseProductId,
                },
                blockUI: true,
                success: function(res) {

                    console.log(res.data);
                    if (res.status == 'error') {
                        alert(res.message);
                    }
                    if (res.status == 'success') {
                        // $('#dummyField').hide();
                        // $('#removeBlock').remove();
                        if ($('input[name="item_name[]"]').val() == '') {
                            $("#sortable .item-row").remove();
                        }
                        let html = '';

                        html += `<div class="d-flex px-4 py-3 c-inv-desc item-row" data-id="${res.data.id}">
                            <div class="d-flex align-items-center">
                                <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                                <input type="hidden"  value="${res.data.id}">
                            </div>
                            <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                                <table width="100%">
                                    <tbody>
                                        <tr class="text-dark-grey font-weight-bold f-14">
                                            <td width="40%" class="border-0 inv-desc-mbl btlr">Description</td>
                                            <td width="15%" class="border-0 inv-desc-mbl btlr">WBC</td>
                                            <td width="15%" class="border-0 inv-desc-mbl btlr">Department</td>
                                            <td width="10%" class="border-0" align="right">Quantity</td>
                                            <td width="10%" class="border-0" align="right">Unit Price</td>
                                            <td width="13%" class="border-0" align="right">Tax%</td>
                                            <td width="17%" class="border-0 bblr-mbl" align="right">Amount</td>
                                        </tr>
                                        <tr>
                                            <td class="border-bottom-0 btrr-mbl btlr">
                                                <input type="text" class="form-control f-14 border-0 w-100 item_name"
                                                    name="item_name[]" placeholder="Item Name" autocomplete="off"
                                                    required value="${res.data.item_name}" readonly>
                                                <input type="hidden" name="product_id[]" value="${res.data.id}">

                                            </td>
                                            <td class="border-bottom-0 btrr-mbl btlr">
                                                <input type="text" class="form-control f-14 border-0 w-100 wbc"
                                                    name="wbc[]" placeholder="WBC" autocomplete="off"
                                                    required value="${res.data.hsn_sac_code}" >
                                            </td>
                                            <td class="border-bottom-0">
                                                <span>${res.departmentDetails.team_name}</span>
                                                <input type="hidden" name="department_id[]" value="${res.data.department_id}">
                                            </td>
                                            <td class="border-bottom-0">
                                                <input type="number" min="1"
                                                    class="form-control f-14 border-0 w-100 text-right quantity"
                                                    value="0" name="quantity[]" autocomplete="off" required>
                                            </td>
                                            <td class="border-bottom-0">
                                                <input type="number" min="1"
                                                    class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                    placeholder="0.00" value="${res.data.unit_price}" name="cost_per_item[]"
                                                    autocomplete="off">
                                            </td>
                                            <td class="border-bottom-0">
                                                <input type="text" min="1"
                                                    class="f-14 border-0 w-100 text-right taxes form-control"
                                                    placeholder="0.00" id="multiselect" name="taxes[]"
                                                    autocomplete="off" value="${res.data.taxes_percent}" readonly>



                                                        </td>
                                            <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                                                <span class="amount-html">0.00</span>
                                                <input type="hidden" class="amount" name="product_amount[]" value="0" autocomplete="off">
                                            </td>
                                        </tr>
                                        <tr class="d-none d-md-table-row d-lg-table-row">
                                            <td colspan="5" class="dash-border-top bblr border-right-0">
                                                <textarea class="f-14 border p-3 rounded w-100 desktop-description form-control"
                                                    name="item_summary[]" placeholder="Enter Description (optional)">
                                                    ${res.data.item_summary}
                                                </textarea>
                                            </td>`;

                        if (res.data.default_image) {
                            html += `<td class="border-left-0">
                                        <img src="/user-uploads/products/${res.data.default_image}" class="border rounded height-35">
                                         <input type="text" name="order_item_image1[]"
                                            data-allowed-file-extensions="png jpg jpeg bmp"
                                            data-height="70" autocomplete="off" value="${res.data.default_image}">
                                    </td>`;
                        } else {
                            html += `<td class="border-left-0">
                                        <input type="file" name="order_item_image[]"
                                            data-allowed-file-extensions="png jpg jpeg bmp"
                                            data-height="70" autocomplete="off">
                                    </td>`;
                        }

                        html += `</tr>
                                    </tbody>
                                </table>
                                <a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item">
                                    <i class="fa fa-times-circle f-20 text-lightest"></i>
                                </a>
                            </div>
                        </div>`;

                        let existingRow = $('#sortable').find(`.item-row[data-id="${res.data.id}"]`);

                        if (existingRow.length > 0) {
                            // Replace the existing row
                            // existingRow.replaceWith(html);
                            alert('already exists');
                        } else {
                            // Append if it doesn't exist
                            $('#sortable').append(html);
                        }
                    }

                }
            });
        }

        $(document).on('click', '.remove-item', function() {
            $(this).closest('.c-inv-desc-table').remove();
            calculateTotalx();
        });

        // Recalculate amounts on input change
        $(document).on('input', '.quantity, .cost_per_item, .taxes', function() {
            const row = $(this).closest('tr');
            calculateRowAmount(row);
            calculateTotalx();
        });
        $('#save-btn1').hide();
        // Calculate row amount
        function calculateRowAmount(row) {
            const quantity = parseFloat(row.find('.quantity').val()) || 0;
            const price = parseFloat(row.find('.cost_per_item').val()) || 0;
            const tax = parseFloat(row.find('.taxes').val()) || 0;
            // alert(tax);
            const baseAmount = quantity * price;
            const taxAmount = (baseAmount * tax) / 100; // Calculate GST
            const totalAmount = baseAmount + taxAmount;

            // Update row with calculated values
            row.find('.amount-html').text(totalAmount.toFixed(2));
            row.find('.amount').val(totalAmount.toFixed(2));
        }

        // Calculate total
        function calculateTotalx() {
            let total = 0;
            $('.amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            console.log(total);
            //  console.log(remaining_amount);
            var contractValue = $('#contractValue').val();
            $('#contract_value').text(contractValue);
            const remain_amt = contractValue - total;
            $('#remain_amt').text(remain_amt);
            $('#remaining-amount').text(remain_amt);
            if (total > contractValue) {
                // alert('Sub total amount cannot be greater than the remaining contract amount!');
                $('#error-message').show();
                $('#save-contract-form').hide();
                $('#save-btn1').show();
                // $('#remaining-amount').text(remaining_amount.toFixed(2));
                // $('.sub-total').text('0.00');
                $('.sub-total-field').val('0.00');
                return;
            }
            $('#save-contract-form').show();
            $('#save-btn1').hide();
            $('#error-message').hide();
            $('.sub-total').text(total.toFixed(2));
            $('.sub-total-field').val(total.toFixed(2));
        }















        $('.save-form').click(function() {
            var type = $(this).data('type');
            var url = "{{ route('bills.billstore') }}";

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            calculateTotal();

            var discount = $('#discount_amount').html();
            var total = $('.sub-total-field').val();

            if (parseFloat(discount) > parseFloat(total)) {
                Swal.fire({
                    icon: 'error',
                    text: "{{ __('messages.discountExceed') }}",

                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                });
                return false;
            }

            $.easyAjax({
                url: "{{ route('bills.billstore') }}" + "?type=" + type,
                container: '#save-bill-data-form',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true, // Commented so that we dot get error of Input variables exceeded 1000
                data: $('#save-bill-data-form').serialize(),
                success: function(response) {
                    window.location.href = "{{ route('bills.index') }}";
                }
            });
        });
    });



</script>
