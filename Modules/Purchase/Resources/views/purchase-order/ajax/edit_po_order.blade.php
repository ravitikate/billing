<style>
    /* Make WBS dropdown options more readable */
    .wbs option {
        white-space: normal;
        padding: 4px 8px;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
    }

    /* Ensure the dropdown itself has proper height for content */
    .wbs {
        min-width: 200px;
        max-width: 250px;
        height: auto !important;
        min-height: 35px;
    }

    /* Make prefix input smaller */
    .prefix {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        text-align: center;
    }

    /* Adjust table cell padding for better fit */
    #itemsTable td {
        padding: 0.5rem 0.25rem;
    }

    /* Make all inputs in modal more compact */
    #editOrderModal .form-control-sm {
        font-size: 0.8rem;
        padding: 0.2rem 0.3rem;
    }
    #logo {
        height: 50px;
    }
    #signatory img {
        height:95px;
        margin-bottom: -40px;
        margin-top: 5px;
        margin-right: 15px;
    }

    span.text-lightest.f-12.f-w-500.mx-2.mw-250.text-truncate {
    display: none !important;
    }

    /* Modal Styles */
    .modal {
        z-index: 1050;
    }
    .modal-backdrop {
        z-index: 1040;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
        padding: 0.5rem;
        vertical-align: middle;
    }
    .form-control-sm {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>

<br>
<!-- ORDER CARD START -->
<div class="card border-0 invoice">
    <!-- CARD BODY START -->
    <div class="card-body">
        <div class="invoice-table-wrapper">
            <table width="100%">
                <tr class="inv-logo-heading">
                    <td><img src="{{ invoice_setting()->logo_url }}" alt="{{ mb_ucwords(company()->company_name) }}"
                            id="logo" /></td>
                    <td align="right" class="font-weight-bold f-21 text-dark text-uppercase mt-4 mt-lg-0 mt-md-0">
                       @if($order->order_type=='purchase_order')
                        @lang('purchase::app.menu.purchaseOrder')
                       @elseif($order->order_type=='work_order')
                        @lang('purchase::app.menu.workOrder')
                       @else

                       @endif
                        </td>
                </tr>
                <tr class="inv-num">
                    <td class="f-14 text-dark">
                        <p class="mt-3 mb-0">
                            {{ mb_ucwords(company()->company_name) }}<br>
                            @if (!is_null($settings) && $order->address)
                                {!! nl2br($order->address->address) !!}<br>
                            @endif
                            {{ company()->company_phone }}
                            @if ($invoiceSetting->show_gst == 'yes' && $order->address)
                                <br>{{ strtoupper($order->address->tax_name) }}: {{ $order->address->tax_number }}
                            @endif
                        </p>
                        <br>
                    </td>
                    <td align="right">
                        <table class="inv-num-date text-dark f-13 mt-3">
                            <tr>
                                <td class="bg-light-grey border-right-0 f-w-500">
                                    @lang('app.orderNumber')</td>
                                <td class="border-left-0">{{ $order->po_number }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light-grey border-right-0 f-w-500">
                                    @lang('modules.orders.orderDate')</td>
                                <td class="border-left-0">{{ $order->purchase_date->translatedFormat(company()->date_format) }}
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
                <tr>
                    <td height="20"></td>
                </tr>
            </table>
            <table width="100%">
                <tr class="inv-unpaid">
                    <td class="f-14 text-dark">
                        <p class="mb-0 text-left">
                            <span class="text-dark-grey ">@lang('modules.invoices.billedTo')</span><br>
                            @if ($order->vendor && $order->vendor->primary_name)
                                {{$order->vendor->primary_name}}<br>
                            @endif
                            @if ($order->vendor && $order->vendor->email)
                                {{$order->vendor->email}}<br>
                            @endif
                            @if ($order->vendor && $order->vendor->phone)
                                {{$order->vendor->phone}}<br>
                            @endif
                            @if ($order->vendor && $order->vendor->company_name)
                                {{mb_ucwords($order->vendor->company_name)}}<br>
                            @endif
                            @if ($order->vendor && $order->vendor->billing_address)
                                {{$order->vendor->billing_address}}<br>
                            @endif
                        </p>
                    </td>
                    <td align="right" class="mt-2 mt-lg-0 mt-md-0">
                        <span class="unpaid {{ $order->billed_status == 'billed' ? 'text-success border-success' : '' }} {{ $order->billed_status == 'unbilled' ? 'text-danger border-danger' : '' }} rounded f-15 ">@lang('purchase::modules.purchaseOrder.' . $order->billed_status)</span>
                    </td>
                </tr>
                <tr>
                    <td height="30" colspan="2"></td>
                </tr>
            </table>
             <table width="100%" class="inv-desc d-none d-lg-table d-md-table">
    <tr>
        <td colspan="2">
            <table class="inv-detail f-14 table-responsive-sm" width="100%">
                {{-- Table Header --}}
                <tr class="i-d-heading bg-light-grey text-dark-grey font-weight-bold">
                    <td class="border-right-0 border-left-0" width="3%">@lang('app.sr')</td>
                     <td class="border-right-0 border-left-0" width="10%">@lang('app.wbs')</td>
                    <td class="border-right-0" width="25%">@lang('app.description')</td>
                    <td class="border-right-0 border-left-0" align="right" width="7%">
                        @lang('modules.invoices.qty')
                    </td>
                    <td class="border-right-0 border-left-0" align="right" width="6%">
                        @lang('modules.invoices.unitPrice') ({{ $order->currency->currency_code }})
                    </td>
                    <td class="border-right-0 border-left-0" align="right" width="8%">
                        @lang('app.retention')%
                    </td>
                    <td class="border-right-0 border-left-0" align="right" width="5%">
                        @lang('modules.invoices.tax')%
                    </td>
                    <td class="border-right-0 border-left-0" align="right" width="9%">
                        @lang('app.gstAmount')
                    </td>
                    <td class="border-left-0" align="right" width="10%">
                        @lang('modules.invoices.amount') ({{ $order->currency->currency_code }})
                    </td>
                    <td class="border-left-0" align="right" width="10%">
                        @lang('modules.invoices.total') ({{ $order->currency->currency_code }})
                    </td>
                </tr>

                {{-- Table Items --}}
                @foreach ($order->items as $item)
                    @if ($item->type == 'item')
                        <tr class="text-dark font-weight-semibold f-13">
                            <td align="right">{{ $loop->iteration }}</td>
                            <td>{{ $item->wbs }}</td>
                             <td>{{ $item->item_name }}</td>

                            @if ($item->quantity == 0)
                                <td colspan="7" align="center">—</td>
                            @else
                                <td align="right">
                                    {{ $item->quantity }}
                                    @if ($item->unit)
                                        <br><span class="f-11 text-dark-grey">{{ $item->unit->unit_type }}</span>
                                    @endif
                                </td>
                                <td align="right">
                                    {{ currency_format($item->unit_price, $order->currency_id, false) }}
                                </td>
                                <td align="right">{{ $item->retention_percent ?? 0 }}%</td>
                                <td align="right">{{ $item->taxes_percent ?? 0 }}%</td>
                                <td align="right">
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->gst_amount)) }}
                                </td>
                                <td align="right">
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->amount)) }}
                                </td>
                                <td align="right">
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->amount + $item->gst_amount)) }}
                                </td>
                            @endif
                        </tr>
                    @endif
                @endforeach

                {{-- Summary Row --}}
                <tr class="bg-light-grey font-weight-bold text-dark-grey">
                    <td colspan="7" class="border-top border-left-0 border-bottom-0"></td>
                    <td align="right">
                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($order->items->sum('gst_amount'))) }}<br>
                        <small>@lang('modules.invoices.tax')</small>
                    </td>
                    <td align="right">
                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($order->items->sum('amount'))) }}<br>
                        <small>@lang('modules.invoices.subTotal')</small>
                    </td>
                    <td align="right">
                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($order->items->sum('amount') + $order->items->sum('gst_amount'))) }}<br>
                        <small>@lang('modules.invoices.total')</small>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


            <table class="inv-note">
                <tr>
                    <td height="30" colspan="2"></td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-info btn-sm ms-2" onclick="openEditModal()">
                            <i class="fa fa-edit"></i> @lang('app.edit')
                        </button>


                    </td>
                </tr>

            </table>

        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" role="dialog" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90%; width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">@lang('app.editOrder')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div style="overflow-x: auto;">
                                <table class="table table-bordered" id="itemsTable" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th width="2%">#</th>
                                            <th width="12%">@lang('app.wbs')</th> <!-- Increased width for WBS content -->
                                            <th width="5%">@lang('app.prefix')</th> <!-- Smaller width for prefix -->
                                            <th width="15%">@lang('app.description')</th>
                                            <th width="5%">@lang('modules.invoices.qty')</th>
                                            <th width="5%">@lang('app.unit')</th>
                                            <th width="7%">@lang('modules.invoices.unitPrice')</th>
                                            <th width="5%">@lang('app.retention')%</th>
                                            <th width="5%">@lang('modules.invoices.tax')%</th>
                                            <th width="8%">@lang('app.gstAmount')</th>
                                            <th width="8%">@lang('modules.invoices.amount')</th>
                                            <th width="8%">@lang('modules.invoices.total')</th>
                                            <th width="2%"></th>
                                        </tr>
                                    </thead>
                                                                        <tbody id="itemsBody">
@foreach ($order->items as $index => $item)
    @if ($item->type == 'item')
    <tr id="row-{{ $index }}" data-item-id="{{ $item->id }}">
        <!-- Hidden Fields -->
        <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $item->product_id ?? '' }}">
        <input type="hidden" name="items[{{ $index }}][budget_head_id]" class="budget-head-id" value="{{ $item->budget_head_id ?? '' }}">
        <input type="hidden" name="items[{{ $index }}][budget_head]" class="budget-head" value="{{ $item->budget_head ?? '' }}">

        <td>{{ $loop->iteration }}</td>
        <td>
            <span class="form-control-static">{{ $item->wbs }}</span>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm prefix"
                value="{{ $item->prefix ?? '' }}"
                placeholder="Prefix" data-index="{{ $index }}"
                style="width: 80px;" readonly>
        </td>
        <td>
            <span class="form-control-static">{{ $item->item_name }}</span>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm quantity"
                name="items[{{ $index }}][quantity]"
                value="{{ $item->quantity }}"
                max="{{ $item->quantity }}"
                step="0.01" min="0" data-index="{{ $index }}"
                style="width: 80px;">
        </td>
        <td>
            <select class="form-control form-control-sm unit"
                name="items[{{ $index }}][unit_id]"
                data-index="{{ $index }}" style="width: 90px;" readonly>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}"
                        {{ $item->unit_id == $unit->id ? 'selected' : '' }}>
                        {{ $unit->unit_type }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm unit-price"
                name="items[{{ $index }}][unit_price]"
                value="{{ $item->unit_price }}"
                step="0.01" min="0" data-index="{{ $index }}"
                style="width: 90px;">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm retention-percent"
                name="items[{{ $index }}][retention_percent]"
                value="{{ $item->retention_percent ?? 0 }}"
                step="1" min="0" max="100" data-index="{{ $index }}"
                style="width: 70px;">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm tax-percent"
                name="items[{{ $index }}][tax_percent]"
                value="{{ $item->taxes_percent ?? 0 }}"
                step="1" min="0" max="100" data-index="{{ $index }}"
                style="width: 70px;">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm gst-amount"
                name="items[{{ $index }}][gst_amount]"
                value="{{ round($item->gst_amount, 2) }}" readonly
                style="width: 90px; background-color: #f8f9fa;">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm amount"
                name="items[{{ $index }}][amount]"
                value="{{ round($item->amount, 2) }}" readonly
                style="width: 90px; background-color: #f8f9fa;">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm total-amount"
                name="items[{{ $index }}][total_amount]"
                value="{{ round($item->amount + $item->gst_amount, 2) }}" readonly
                style="width: 90px; background-color: #f8f9fa;">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger remove-item" onclick="removeRow(this)">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
    @endif
@endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="12">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="addMoreItem()">
                                                    <i class="fa fa-plus"></i> @lang('app.addMore')
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-right"><strong>@lang('modules.invoices.subTotal'):</strong></td>
                                            <td colspan="4">
                                                <input type="text" class="form-control form-control-sm" id="modalSubtotal" value="{{ round($order->items->sum('amount'), 2) }}" readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-right"><strong>@lang('modules.invoices.totalTax'):</strong></td>
                                            <td colspan="4">
                                                <input type="text" class="form-control form-control-sm" id="modalTotalTax" value="{{ round($order->items->sum('gst_amount'), 2) }}" readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="text-right"><strong>@lang('modules.invoices.total'):</strong></td>
                                            <td colspan="4">
                                                <input type="text" class="form-control form-control-sm" id="modalGrandTotal" value="{{ round($order->items->sum('amount') + $order->items->sum('gst_amount'), 2) }}" readonly>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="saveOrder()">@lang('app.save')</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('app.close')</button>
            </div>
        </div>
    </div>
</div>

<!-- CARD FOOTER END -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>

<script>
    // Initialize variables
     let itemIndex = {{ count($order->items->where('type', 'item')) }};
    let orderId = {{ $order->id }};
    let currencyCode = "{{ $order->currency->currency_code }}";

// Calculate function for a single row
function calculateRow(row) {
    let quantity = parseFloat($(row).find('.quantity').val()) || 0;
    let unitPrice = parseFloat($(row).find('.unit-price').val()) || 0;
    let retentionPercent = parseFloat($(row).find('.retention-percent').val()) || 0;
    let taxPercent = parseFloat($(row).find('.tax-percent').val()) || 0;

    // Calculate basic amount (quantity * unit price)
    let basicAmount = quantity * unitPrice;

    // Calculate retention amount
    let retentionAmount = (basicAmount * retentionPercent) / 100;

    // Calculate amount after retention
    let amountAfterRetention = basicAmount - retentionAmount;

    // Calculate GST on amount after retention
    let gstAmount = (amountAfterRetention * taxPercent) / 100;

    // Update fields with 2 decimal places
    $(row).find('.amount').val(amountAfterRetention.toFixed(2));
    $(row).find('.gst-amount').val(gstAmount.toFixed(2));
    $(row).find('.total-amount').val((amountAfterRetention + gstAmount).toFixed(2));

    calculateModalTotals();
}

    // Calculate all totals in modal
  function calculateModalTotals() {
        let subtotal = 0;
        let totalTax = 0;
        let totalBasicAmount = 0;

        $('#itemsBody tr').each(function() {
            let amount = parseFloat($(this).find('.amount').val()) || 0;
            let gst = parseFloat($(this).find('.gst-amount').val()) || 0;
            let quantity = parseFloat($(this).find('.quantity').val()) || 0;
            let unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;

            subtotal += amount;
            totalTax += gst;
            totalBasicAmount += (quantity * unitPrice);
        });

        let grandTotal = subtotal + totalTax;

        $('#modalSubtotal').val(subtotal.toFixed(2));
        $('#modalTotalTax').val(totalTax.toFixed(2));
        $('#modalGrandTotal').val(grandTotal.toFixed(2));

        // Optional: Display basic total if needed
        // $('#modalBasicTotal').val(totalBasicAmount.toFixed(2));
    }


function openEditModal() {
        $('#editOrderModal').modal('show');
        setTimeout(function() {
            calculateModalTotals();
        }, 500);
    }

     $('#editOrderModal').on('shown.bs.modal', function() {
        $('#itemsBody tr').each(function() {
            calculateRow($(this));
        });
    });

    // Delete order
    function deleteOrder() {
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
                var token = "{{ csrf_token() }}";
                var url = "{{ route('purchase-order.destroy', ':id') }}";
                url = url.replace(':id', orderId);

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
                            window.location.href = "{{ route('purchase-order.index') }}";
                        }
                    }
                });
            }
        });
    }

    // Quantity change validation
    $(document).on('change', '.quantity', function() {
        let maxQty = parseFloat($(this).attr('max'));
        let currentQty = parseFloat($(this).val());

        if (maxQty && currentQty > maxQty) {
            Swal.fire({
                icon: 'error',
                title: '@lang('app.error')',
                text: 'Quantity cannot exceed ' + maxQty,
                timer: 3000,
                showConfirmButton: false
            });
            $(this).val(maxQty);
        }
        calculateRow($(this).closest('tr'));
    });

    // Other field changes
  $(document).on('input', '.quantity, .unit-price, .retention-percent, .tax-percent', function() {
        calculateRow($(this).closest('tr'));
    });

    // Handle change events as well for dropdowns and number inputs
    $(document).on('change', '.unit-price, .retention-percent, .tax-percent, .unit', function() {
        calculateRow($(this).closest('tr'));
    });

// Add more item
function addMoreItem() {
    let newIndex = itemIndex++;
    let html = `
        <tr id="row-${newIndex}">
            <!-- Hidden Fields -->
            <input type="hidden" class="product-id" name="items[${newIndex}][product_id]" value="">
            <input type="hidden" class="budget-head-id" name="items[${newIndex}][budget_head_id]" value="">
            <input type="hidden" class="budget-head" name="items[${newIndex}][budget_head]" value="">

            <td>${newIndex + 1}</td>
            <td>
                <select class="form-control form-control-sm wbs" name="items[${newIndex}][wbs]" data-index="${newIndex}" style="width: 100%;">
                    <option value="">-- Select WBS --</option>
                    @foreach ($wbsList as $wbsItem)
                        <option value="{{ $wbsItem }}" style="white-space: normal;">{{ $wbsItem }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm prefix"
                    name="items[${newIndex}][prefix]"
                    placeholder="Prefix" data-index="${newIndex}" style="width: 80px;" >
            </td>
            <td>
                <textarea class="form-control form-control-sm item-name-textarea"
                    name="items[${newIndex}][item_name]"
                    rows="2" placeholder="Enter description"></textarea>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm quantity"
                    name="items[${newIndex}][quantity]"
                    step="0.01" min="0.01" value="1" data-index="${newIndex}" style="width: 80px;">
            </td>
            <td>
                <select class="form-control form-control-sm unit"
                    name="items[${newIndex}][unit_id]"
                    data-index="${newIndex}" style="width: 90px;">
                    <option value="">-- Select Unit --</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm unit-price"
                    name="items[${newIndex}][unit_price]"
                    step="0.01" min="0.01" value="0" data-index="${newIndex}" style="width: 90px;">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm retention-percent"
                    name="items[${newIndex}][retention_percent]"
                    step="0.01" min="0" max="100" value="0" data-index="${newIndex}" style="width: 70px;">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm tax-percent"
                    name="items[${newIndex}][tax_percent]"
                    step="0.01" min="0" max="100" value="0" data-index="${newIndex}" style="width: 70px;">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm gst-amount"
                    name="items[${newIndex}][gst_amount]"
                    readonly value="0" style="width: 90px; background-color: #f8f9fa;">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm amount"
                    name="items[${newIndex}][amount]"
                    readonly value="0" style="width: 90px; background-color: #f8f9fa;">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm total-amount"
                    name="items[${newIndex}][total_amount]"
                    readonly value="0" style="width: 90px; background-color: #f8f9fa;">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-item" onclick="removeRow(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $('#itemsBody').append(html);
}
    // Remove row
    function removeRow(button) {
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.yes')",
            cancelButtonText: "@lang('app.cancel')",
        }).then((result) => {
            if (result.isConfirmed) {
                $(button).closest('tr').remove();
                calculateModalTotals();

                // Re-number rows
                $('#itemsBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }
        });
    }

    // Save order
// Save order
function saveOrder() {
    let items = [];
    let isValid = true;

    $('#itemsBody tr').each(function() {
        // Check WBS (for new items)
        let wbsValue = $(this).find('.wbs').val();
        if (!$(this).data('item-id') && (!wbsValue || wbsValue === '')) {
            isValid = false;
            $(this).find('.wbs').addClass('is-invalid');
            return false;
        } else {
            $(this).find('.wbs').removeClass('is-invalid');
        }

        // Check Description - for existing items (span) and new items (textarea)
        let itemName = '';
        let descriptionElement = $(this).find('.item-name-textarea');

        if (descriptionElement.length > 0) {
            // This is a new item with textarea
            itemName = descriptionElement.val();
        } else {
            // This is an existing item with span
            itemName = $(this).find('td:eq(3) span').text().trim();
        }

        if (!itemName || itemName === '') {
            isValid = false;
            if (descriptionElement.length > 0) {
                descriptionElement.addClass('is-invalid');
            }
            return false;
        } else {
            if (descriptionElement.length > 0) {
                descriptionElement.removeClass('is-invalid');
            }
        }

        // Check Prefix (optional, but ensure it has value if it's a new item)
        let prefix = $(this).find('.prefix').val();

        // Get quantity and unit price
        let quantity = parseFloat($(this).find('.quantity').val()) || 0;
        let unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;

        // Validate quantity and unit price for all items
        if (quantity <= 0) {
            isValid = false;
            $(this).find('.quantity').addClass('is-invalid');
            return false;
        } else {
            $(this).find('.quantity').removeClass('is-invalid');
        }

        if (unitPrice <= 0) {
            isValid = false;
            $(this).find('.unit-price').addClass('is-invalid');
            return false;
        } else {
            $(this).find('.unit-price').removeClass('is-invalid');
        }

        // Get unit selection
        let unitId = $(this).find('.unit').val();
        if (!unitId) {
            isValid = false;
            $(this).find('.unit').addClass('is-invalid');
            return false;
        } else {
            $(this).find('.unit').removeClass('is-invalid');
        }

        items.push({
            id: $(this).data('item-id') || null,
            product_id: $(this).find('.product-id').val(),
            budget_head_id: $(this).find('.budget-head-id').val(),
            budget_head: $(this).find('.budget-head').val(),
            wbs: wbsValue,
            prefix: prefix,
            item_name: itemName,
            quantity: quantity,
            unit_id: unitId,
            unit_price: unitPrice,
            retention_percent: $(this).find('.retention-percent').val() || 0,
            tax_percent: $(this).find('.tax-percent').val() || 0,
            amount: $(this).find('.amount').val(),
            gst_amount: $(this).find('.gst-amount').val(),
            total_amount: $(this).find('.total-amount').val()
        });
    });

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: '@lang('app.error')',
            text: 'Please fill all required fields correctly (WBS, Description, Quantity, Unit Price, and Unit)',
        });
        return;
    }

    // Debug: See what data is being sent
    console.log('Items to save:', items);

    let data = {
        _token: "{{ csrf_token() }}",
        items: items,
        subtotal: $('#modalSubtotal').val(),
        total_tax: $('#modalTotalTax').val(),
        total: $('#modalGrandTotal').val()
    };

    $.easyAjax({
        url: "{{ route('purchase-order.update_po', ':id') }}".replace(':id', orderId),
        type: "POST",
        data: data,
        success: function(response) {
            if (response.status == 'success') {
                $('#editOrderModal').modal('hide');
                window.location.reload();
            }
        }
    });
}
    // Your existing JavaScript functions remain the same below
      var clipboard = new ClipboardJS('.btn-copy');

    clipboard.on('success', function(e) {
        Swal.fire({
            icon: 'success',
            text: '@lang('app.copied')',
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

    $('body').on('click', '.sendButton', function() {
        var id = $(this).data('invoice-id');
        var token = "{{ csrf_token() }}";
        var type = $(this).data('type');

        var url = "{{ route('purchase_order.send_order', ':id') }}";
        url = url.replace(':id', id);

        $.easyAjax({
            type: 'POST',
            url: url,
            container: '.content-wrapper',
            blockUI: true,
            data: {
                '_token': token,
                'data_type': type,
                'type': 'send'
            },
            success: function(response) {
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        });
    });

    $('body').on('click', '.reminderButton', function() {
        var id = $(this).data('invoice-id');
        var token = "{{ csrf_token() }}";

        var url = "{{ route('invoices.payment_reminder', ':id') }}";
        url = url.replace(':id', id);

        $.easyAjax({
            type: 'GET',
            container: '#invoices-table',
            blockUI: true,
            url: url,
            success: function(response) {
                if (response.status == "success") {
                    $.unblockUI();
                }
            }
        });
    });

    $('body').on('click', '.cancel-invoice', function() {
        var id = $(this).data('invoice-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.invoiceText')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.yes')",
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
                var token = "{{ csrf_token() }}";

                var url = "{{ route('invoices.update_status', ':id') }}";
                url = url.replace(':id', id);

                $.easyAjax({
                    type: 'GET',
                    url: url,
                    container: '#invoices-table',
                    blockUI: true,
                    success: function(response) {
                        if (response.status == "success") {
                            window.location.reload();
                        }
                    }
                });
            }
        });
    });

    $('body').on('click', '.toggle-shipping-address', function() {
        let invoiceId = $(this).data('invoice-id');

        let url = "{{ route('invoices.toggle_shipping_address', ':id') }}";
        url = url.replace(':id', invoiceId);

        $.easyAjax({
            url: url,
            type: 'GET',
            container: '#invoices-table',
            blockUI: true,
            success: function(response) {
                if (response.status === 'success') {
                    window.location.reload();
                }
            }
        });
    });

    $('body').on('click', '.add-shipping-address', function() {
        let invoiceId = $(this).data('invoice-id');

        var url = "{{ route('invoices.shipping_address_modal', [':id']) }}";
        url = url.replace(':id', invoiceId);

        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.invoice-upload', function() {
        var invoiceId = $(this).data('invoice-id');
        const url = "{{ route('invoices.file_upload') }}?invoice_id=" + invoiceId;
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.delete-file', function() {
        let id = $(this).data('row-id');
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
                var url = "{{ route('invoice-files.destroy', ':id') }}";
                url = url.replace(':id', id);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            $('#invoice-file-list').html(response.view);
                        }
                    }
                });
            }
        });
    });
</script>
