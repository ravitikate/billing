<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<style>
    .mt-9 {
        margin-top: 48px;
    }
</style>
<!-- CREATE BILL
     START -->
<div class="bg-white rounded b-shadow-4 create-inv">


    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.bill')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="save-bill-data-form">
        @method('PUT')
        <!-- BILL NUMBER, DATE, FREQUENCY START -->
        <div class="row px-lg-4 px-md-4 px-3 pt-3">
            <!-- BILL NUMBER START -->
            
             <div class="col-md-4">
               
                
                <div class=" form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="" fieldId="bill_number" :fieldLabel="__('purchase::app.menu.billNumber')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="bill_number" name="bill_number"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('app.bill_number')" value="{{ $purchaseBillId->purchase_bill_number }}" readOnly>
                    </div>
            </div></div>
            <!-- BILL NUMBER END -->
            <div class="col-md-4" hidden>
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="" fieldId="reference_bill_no" :fieldLabel="__('app.reference_bill_no')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="reference_bill_no" name="reference_bill_no"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('app.reference_bill_no')" value="{{ $purchaseBillId->reference_bill_no }}">
                    </div>
                </div>
            </div>
            <!-- SELECT VENDOR -->
            <div class="col-md-4">
                <x-forms.label fieldId="vendor-id" :fieldLabel="__('purchase::app.selectVendor')">
                </x-forms.label>
                <input type="hidden" value="{{ $purchaseBillId->purchaseVendor->id }}" name="vendor_id">
                <input type="text" readOnly name="vendor_name" id="vendor_id" class="form-control height-35 f-15"
                    value="{{ $purchaseBillId->purchaseVendor->primary_name }}">
            </div>
            <!-- SELECT VENDOR -->

            <!-- BILL DATE START -->
            <!--<div class="col-md-4">-->
            <!--    <div class="form-group mb-lg-0 mb-md-0 mb-4">-->
            <!--        <x-forms.label fieldId="due_date" :fieldLabel="__('purchase::app.menu.billDate')">-->
            <!--        </x-forms.label>-->
            <!--        <div class="input-group">-->
            <!--            <input type="text" id="bill_date" name="issue_date"-->
            <!--                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"-->
            <!--                placeholder="@lang('placeholders.date')" value="{{ $bill_Date }}">-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</div>-->
            @php
    $minDate = \Carbon\Carbon::parse($bill_Date)->format('Y-m-d'); // for JS use
    $displayDate = \Carbon\Carbon::parse($bill_Date)->format('D d M Y'); // for input field
@endphp
<div class="col-md-4">
    <div class="form-group mb-lg-0 mb-md-0 mb-4">
        <x-forms.label fieldId="bill_date" :fieldLabel="__('purchase::app.menu.billDate')" />
        <div class="input-group">
            <input type="text" id="bill_date" name="issue_date"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                placeholder="@lang('placeholders.date')" value="{{ $displayDate }}" readonly>
        </div>
    </div>
</div>
            <!-- BILL DATE END -->




        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-3">
                <x-forms.label fieldId="purchase_order" :fieldLabel="__('purchase::app.purchaseOrder.purchaseOrder')">
                </x-forms.label>
                <div class="form-group c-inv-select mb-2">
                    <input readOnly type="text" id="purchase_order" name="purchase_order"
                        class="px-6 positioissue_daten-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                        value="{{ $order_id->purchase_order_id }}">
                </div>
            </div>
        </div>

        <div id="sortable">
            <!-- DESKTOP DESCRIPTION TABLE START -->
            @foreach ($purchaseBill as $item)
                <div class="d-flex px-4 py-3 c-inv-desc item-row" id="bill-desc-{{ $item->id }}">
                    <div class="c-inv-desc-table w-100">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-light">
                                <tr class="text-dark-grey font-weight-bold text-center">
                                    <!--<th width="37%">Description</th>-->
                                    <!--<th width="6%">WBS</th>-->
                                    <!--<th width="15%">  Total</th> -->
                                    <!--<th width="10%">Quantity</th>-->
                                    <!--<th width="10%">Unit Price</th>-->
                                    <!--<th width="11%">Tax%</th>-->
                                    <!--<th width="11%">Amount</th> -->

                                    <th width="30%">Description</th>
                                    <th width="17%">WBS</th>
                                    <th width="10%">Total</th>
                                    <th width="7%">Quantity</th>
                                    <th width="7%">Unit Price</th>
                                    <th width="7%">Tax%</th>
                                    <th width="10%">Amount</th>
                                    <th width="10%">Total</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <p>{{ $item->item_name }}</p>
                                        <input type="hidden" class="form-control-plaintext item_name"
                                            name="item_name[]" value="{{ $item->item_name }}" readonly>
                                        <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                        <input type="hidden" name="department_id[]"
                                            value="{{ $item->department_id }}">
                                    </td>
                                    <td>
                                        <p>{{ $item->wbs }}</p>
                                        <input type="hidden" class="form-control wbc" name="wbc[]" placeholder="WBS"
                                            value="{{ $item->wbs }}" readonly>
                                        <input type="hidden" class="form-control budget_head" name="budget_head[]"
                                            placeholder="WBS" value="{{ $item->budget_head }}" readonly>
                                        <input type="hidden" class="form-control retention_amount"
                                            name="retention_amount[]" value="{{ $item->retention_amount }}" readonly>

                                    </td>
                                    <td>
                                        <div>
                                            Quantity: {{ $item->total_quantity }}
                                            <p class="my-0">
                                                <span class="text-success mt-1">Remaining:</span>
                                                {{ number_format((float) $item->total_quantity - (float) $item->quantity + (float) $item->bill_quantity, 2) }}
                                            </p>
                                        </div>

                                        <input type="hidden" class="form-control text-right" name="total_quantity[]"
                                            value="{{ $item->total_quantity }}" readonly>
                                        <input type="hidden" class="form-control text-right"
                                            name="remaining_quantity[]" data-item-id="{{ $item->id }}"
                                            value="{{ number_format((float) $item->total_quantity - (float) $item->quantity + (float) $item->bill_quantity, 2) }}"
                                            readonly>
                                    </td>

                                    <td>
                                        <input type="text" class="form-control text-right quantity"
                                            data-item-id="{{ $item->id }}" name="quantity[]"
                                            value="{{ $item->quantity }}"
                                            max="{{ max(0, $item->total_quantity - $item->quantity + $item->bill_quantity) }}"
                                            oninput="validateQuantity(this)">
                                    </td>
                                    <td>
                                        <div> {{ $item->unit_price }} ({{ strtolower($item->unit) }})</div>
                                        <input type="hidden" class="form-control-plaintext text-right cost_per_item"
                                            data-item-id="{{ $item->id }}" name="cost_per_item[]"
                                            value="{{ $item->unit_price }}" readonly>
                                        <input type="hidden" class="cost_per_item" name="unit[]"
                                            value="{{ $item->unit }}" data-item-id="{{ $item->id }}">
                                        <input type="hidden" class="cost_per_item" name="unit_id[]"
                                            value="{{ $item->unit_id }}" data-item-id="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="select-others">
                                            <span data-rate="18"> GST: 18% </span><br>
                                        </div>
                                        <span class="text-success mt-1">Amount:</span>
                                        <span class="tax-amount" data-item-id="{{ $item->id }}"> <span
                                                class="tax-value">{{ $item->tax_amount }}</span> </span>
                                        <input type="hidden" class="taxes" name="taxes[]"
                                            value="{{ $item->taxes_percent }}" readonly>
                                        <input type="hidden" class="taxes_amount" name="tax_amount[]"
                                            value="{{ $item->tax_amount }}">
                                        <input type="hidden" class="row_id" name="row_id[]"
                                            value="{{ $item->id }}">
                                    </td>

                                    <td class="text-right">
                                        <span class="amount-html"
                                            data-item-id="{{ $item->id }}">{{ $item->amount }}</span>
                                        <input type="hidden" class="amount" name="product_amount[]"
                                            data-item-id="{{ $item->id }}" value="{{ $item->amount }}">
                                    </td>

                                    <td class="text-right">
                                        <span class="total-amount-html" data-item-id="{{ $item->id }}"
                                            value="{{ $item->amount + $item->tax_amount }}">0.00</span>
                                    </td>


                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <script>
                        function calculateRowAmount($row) {
                            var quantity = parseFloat($row.find(".quantity").val()) || 0;
                            var unitPrice = parseFloat($row.find(".cost_per_item").val()) || 0;
                            var amount = quantity * unitPrice;

                            var taxRate = parseFloat($row.find(".taxes").val()) || 0;
                            var taxAmount = (amount * taxRate) / 100;
                            var totalAmount = amount + taxAmount;

                            $row.find(".tax-amount").html(taxAmount.toFixed(2));
                            $row.find(".amount-html").html(amount.toFixed(2));
                            $row.find(".amount").val(amount.toFixed(2));
                            $row.find(".taxes_amount").val(taxAmount.toFixed(2));

                            // ✅ Update the Total (Amount + Tax)
                            $row.find(".total-amount-html").html(totalAmount.toFixed(2));
                        }


                        function calculateAllTotals() {
                            let subtotal = 0;
                            let taxTotal = 0;

                            $(".item-row").each(function() {
                                const $row = $(this);
                                const quantity = parseFloat($row.find(".quantity").val()) || 0;
                                const unitPrice = parseFloat($row.find(".cost_per_item").val()) || 0;
                                const taxRate = parseFloat($row.find(".taxes").val()) || 0;

                                const amount = quantity * unitPrice;
                                const taxAmount = (amount * taxRate) / 100;

                                subtotal += amount;
                                taxTotal += taxAmount;
                            });

                            $(".sub-total").html(subtotal.toFixed(2));
                            $(".tax-total").html(taxTotal.toFixed(2));
                            $(".total").html((subtotal + taxTotal).toFixed(2));
                            $(".total_a").val((subtotal + taxTotal).toFixed(2));
                            $(".tax-total5").val(taxTotal.toFixed(2));
                            $(".sub-total1").val(subtotal.toFixed(2));
                            $(".tax-total1").val(taxTotal.toFixed(2));
                            $(".tax-total4").val(taxTotal.toFixed(2));
                            $(".total1").val((subtotal + taxTotal).toFixed(2));
                        }

                        function validateQuantity(input) {
                            const maxQuantity = parseFloat(input.getAttribute('max'));
                            let enteredQuantity = parseFloat(input.value.trim());

                            if (isNaN(enteredQuantity) || enteredQuantity < 0) {
                                alert('Please enter a valid quantity greater than or equal to 0.');
                                input.value = 0;
                                return;
                            }

                            if (enteredQuantity > maxQuantity) {
                                alert(`Quantity cannot exceed the remaining quantity of ${maxQuantity}.`);
                                input.value = maxQuantity;
                            }

                            const $row = $(input).closest(".item-row");
                            calculateRowAmount($row);
                            calculateAllTotals();
                        }

                        // Attach handler
                        $(document).on("input", ".quantity", function() {
                            validateQuantity(this);
                        });

                        // Initial calculation on page load
                        $(document).ready(function() {
                            $(".item-row").each(function() {
                                calculateRowAmount($(this));
                            });
                            calculateAllTotals();
                        });
                    </script>

                </div>
            @endforeach
            <!-- DESKTOP DESCRIPTION TABLE END -->

            <hr class="m-0 border-top-grey mt-2">
            <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
                <table width="100%" class="text-right f-14">
                    <tbody>
                        <tr>
                            <td width="70%" class="right">Total</td>
                            <td class="tax-total">0.00</td>
                            <input type="hidden" class="tax-total1 field" name="total_tax" value="0">
                            <td class="border-top-0 sub-total">0.00</td>
                            <input type="hidden" class="sub-total1 field" name="sub_total" value="0">
                            <td class="text-dark total">0.00</td>
                            <input type="hidden" class="total1 field" name="total_amount" value="0">
                        </tr>
                    </tbody>
                </table>
            </div>



            <!-- DESKTOP DESCRIPTION TABLE END -->

        </div>
        <hr class="m-0 border-top-grey">

        <div id="fileInputs">

            <div class="px-3 pt-3 row px-lg-4 px-md-4">
                 <div class="col-sm-12">
                            <h6>@lang('app.uploadDoc')</h6>
                        </div>
                @if ( count($purchase_bills_files) > 0)
                    @foreach ($purchase_bills_files as $item)
                        <div class="col-sm-2 mb-3 file-card" id="file-{{ $item->id }}">
                            <div class="border p-2 rounded">
                                <p class="mb-1"><strong>File Name</strong>: {{ $item->custom_title }}</p>

                                <a href="{{ asset('bill-files/' . $item->stored_name) }}" download
                                    class="btn btn-sm btn-success">
                                    <i class="fa fa-download"></i>
                                </a>

                                <button type="button" class="btn btn-sm btn-info delete-btn"
                                    data-id="{{ $item->id }}"
                                    data-url="{{ route('bill-file.delete', $item->id) }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-sm-12 mb-3 file-card noFileAvailable">
                        <div class="border p-2 rounded">
                            <h6>@lang('app.noFile')</h6>
                        </div>
                    </div>
                @endif


            </div>
        </div>
        <div class="px-3 pt-3 row px-lg-4 px-md-4">
            <div class="col-md-12">
                <button type="button" class="btn btn-info" onclick="addFileInput()">Add More</button><br>
            </div>
        </div>
        <br>
        <hr class="m-0 border-top-grey">



        <!-- NOTE AND TERMS AND CONDITIONS START -->
        <div class="d-flex flex-wrap px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-sm-12 c-inv-note-terms p-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="" class="" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4" placeholder="@lang('placeholders.invoices.note')">{{ $order_id->note }}</textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p id="error_message" style=" color: red;text-align:center; font-size:18px"></p>
            </div>
        </div>

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
                                data-type="abstract">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.resubmit')
                            </a>
                        </li>


                    </ul>
                </div>
            </div>

            <x-forms.button-cancel :link="route('bills.index')" class="border-0 ">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const minDate = new Date("{{ $minDate }}");

        const dp1 = datepicker('#bill_date', {
            position: 'bl',
            autohide: true,
            minDate: minDate,
            format: 'D dd M yyyy',  // Displays as: Fri 29 Aug 2025
        });
    });
</script>
<script>
    $('.save-form').click(function() {
        var type = $(this).data('type');
        if (type != undefined) {
            var url = "{{ route('bills.update', $order_id->id) }}" + "?type=" + type;
        } else {
            var url = "{{ route('bills.update', $order_id->id) }}";
        }

        if (KTUtil.isMobileDevice()) {
            $('.desktop-description').remove();
        } else {
            $('.mobile-description').remove();
        }

        calculateTotal();

        $.easyAjax({
            url: url,
            container: '#save-bill-data-form',
            type: "POST",
            blockUI: true,
            redirect: true,
            file: true, // Commented so that we dot get error of Input variables exceeded 1000
            data: $('#save-bill-data-form').serialize(),
            success: function(response) {
                // console.log(response);
                if (response.error) {
                    $('#error_message').text(response.error);
                } else {
                    window.location.href = "{{ route('vendor-credits.show-abstract') }}";
                }

            }
        });
    });
</script>
<script>
    function addFileInput() {
        $('.noFileAvailable').hide();
        const group = document.createElement('div');
        group.className = 'file-group';
        group.innerHTML = `<div class="px-3 pt-3 row px-lg-4 px-md-4">
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="file_titles">Name of
                                file</label>
                            <div class="input-group">
                                <input type="text"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="file_titles" name="file_titles[]" placeholder="Name of file" autocomplete="off" >
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="files"> Document</label>
                            <div class="input-group">
                                <input type="file"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="files" name="files[]" accept="application/pdf" >
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn-danger mt-9 remove-btn"
                            onclick="removeFileInput(this)">Remove</button>
                    </div>
                </div>`;
        document.getElementById('fileInputs').appendChild(group);
    }

    function removeFileInput(button) {
        const fileGroup = button.closest('.file-group');
        if (fileGroup) {
            fileGroup.remove();
        }
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const fileId = this.getAttribute('data-id');

                if (confirm('Are you sure you want to delete this file?')) {
                    fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        })
                        .then(response => {
                            if (response.ok) {
                                // Remove the DOM element on success
                                const fileCard = document.getElementById('file-' + fileId);
                                if (fileCard) {
                                    fileCard.remove();
                                }
                            } else {
                                alert('Failed to delete the file.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Something went wrong.');
                        });
                }
            });
        });
    });
</script>
