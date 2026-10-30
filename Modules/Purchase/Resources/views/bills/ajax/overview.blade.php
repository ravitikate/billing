<style>
    #logo {
        height: 50px;
    }

    #signatory img {
        height: 95px;
        margin-bottom: -40px;
        margin-top: 5px;
        margin-right: 15px;
    }
</style>

<div class="card border-0 invoice">
    <!-- CARD BODY START -->
    <div class="card-body">

        @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <i class="fa fa-check"></i> {!! $message !!}
            </div>
            <?php Session::forget('success'); ?>
        @endif

        @if ($message = Session::get('error'))
            <div class="custom-alerts alert alert-danger fade in">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                {!! $message !!}
            </div>
            <?php Session::forget('error'); ?>
        @endif

        <div class="invoice-table-wrapper">
            <table width="100%">
                <tr class="inv-logo-heading">
                    <td><img src="{{ invoice_setting()->logo_url }}" alt="{{ mb_ucwords(company()->company_name) }}"
                            id="logo" /></td>
                    <td align="right" class="font-weight-bold f-21 text-dark text-uppercase mt-4 mt-lg-0 mt-md-0">
                        @lang('purchase::app.menu.bill')</td>
                </tr>
                <tr class="inv-num">
                    <td class="f-14 text-dark">
                        <p class="mt-3 mb-0">
                            {{ mb_ucwords(company()->company_name) }}<br>
                            @if (!is_null($purchaseBill) && $purchaseBill->order->address)
                                {!! nl2br($purchaseBill->order->address->address) !!}<br>
                            @endif
                            {{ company()->company_phone }}
                        </p><br>
                    </td>
                    <td align="right">
                        <table class="inv-num-date text-dark f-13 mt-3">
                            <tr>
                                <td class="bg-light-grey border-right-0 f-w-500">
                                    @lang('purchase::app.menu.billNumber')</td>
                                <td class="border-left-0">{{ $purchaseBill->bill_number }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light-grey border-right-0 f-w-500">
                                    @lang('purchase::app.menu.billDate')</td>
                                <td class="border-left-0">
                                    {{ $purchaseBill->bill_date->translatedFormat(company()->date_format) }}
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

                            @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->primary_name)
                                {{ mb_ucwords($purchaseBill->purchaseVendor->primary_name) }}<br>
                            @endif

                            @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->email)
                                {{ $purchaseBill->purchaseVendor->email }}<br>
                            @endif

                            @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->phone)
                                {{ $purchaseBill->purchaseVendor->phone }}<br>
                            @endif

                            @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->company_name)
                                {{ mb_ucwords($purchaseBill->purchaseVendor->company_name) }}<br>
                            @endif

                            @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->billing_address)
                                {!! nl2br($purchaseBill->purchaseVendor->billing_address) !!}
                            @endif
                        </p>
                    </td>
                    @if ($purchaseBill->purchaseVendor && $purchaseBill->purchaseVendor->shipping_address)
                        <td align="right" class="f-14 text-black">
                            <p>
                                <span class="text-dark-grey ">@lang('app.shippingAddress')</span><br>
                                {!! nl2br($purchaseBill->purchaseVendor->shipping_address) !!}
                            </p>
                        </td>
                    @endif
                </tr>
                <tr>
                    <td height="30" colspan="2"></td>
                </tr>
            </table>
            @php
                $srNo = 1;
                $totalAmount = 0;
                $totalTax = 0;
            @endphp

            <table width="100%" class="inv-desc d-none d-lg-table d-md-table">
                <tr>
                    <td colspan="2">
                        <table class="inv-detail f-14 table-responsive-sm" width="100%">
                            <tr class="i-d-heading bg-light-grey text-dark-grey font-weight-bold">
                                <td class="border-right-0" width="5%">@lang('app.sr')</td>
                                <!--<td class="border-right-0" width="15%">@lang('app.wbs')</td>-->
                                <td class="border-right-0" width="46%">@lang('app.description')</td>
                                <td class="border-right-0 border-left-0" align="right">@lang('modules.invoices.qty')</td>
                                <td class="border-right-0 border-left-0" align="right">@lang('modules.invoices.unitPrice')
                                    ({{ $orderCurrency->currency->currency_code }})</td>
                                <td class="border-right-0 border-left-0" align="right">@lang('modules.invoices.tax') %</td>
                                <td class="border-right-0 border-left-0" align="right">@lang('modules.invoices.tax') Amount</td>
                                <td class="border-left-0" align="right" width="10%">@lang('modules.invoices.amount')</td>
                                <td class="border-left-0" align="right" width="10%">@lang('modules.invoices.total')</td>
                            </tr>

                            @foreach ($purchaseBillItem as $item)
                                @if ($item->type == 'bill_item' && !empty($item->wbs) && !empty($item->quantity) && $item->quantity > 0)
                                    @php
                                        $lineTotal = $item->amount + $item->tax_amount;
                                        $totalAmount += $item->amount;
                                        $totalTax += $item->tax_amount;
                                    @endphp
                                    <tr class="text-dark font-weight-semibold f-13">
                                        <td>{{ $srNo++ }}</td>
                                        <!--<td>{{ $item->wbs }}</td>-->
                                        <td>{{ $item->item_name }}</td>
                                        <td align="right">{{ $item->quantity }} ({{ $item->unit }})</td>
                                        <td align="right">{{ $item->unit_price }}</td>
                                        <td align="right">{{ $item->taxes_percent }}%</td>
                                        <td align="right">{{ $item->tax_amount }}</td>
                                        <td align="right">{{ $item->amount }}</td>
                                        <td align="right">{{ $lineTotal }}</td>
                                    </tr>
                                @endif
                            @endforeach

                            <tr class="bg-light-grey font-weight-bold text-dark-grey">
                                <td colspan="5" class="border-top border-left-0 border-bottom-0"></td>
                                <td align="right">
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($totalTax)) }}<br>
                                    <small>@lang('modules.invoices.tax')</small>
                                </td>
                                <td align="right">
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($totalAmount)) }}<br>
                                    <small>@lang('modules.invoices.subTotal')</small>
                                </td>
                                <td align="right">
                                    ₹
                                    {{ \App\Helper\NumberFormat::formatIndianCurrency(round($totalAmount + $totalTax)) }}<br>
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
                    <td>@lang('app.note')</td>
                </tr>
                <tr>
                    <div class="row pt-3">
                        <div class="col-sm-12">
                            <h6>@lang('app.uploadedDoc')</h6>
                        </div>
                        @if (count($purchase_bills_files) > 0)
                            @foreach ($purchase_bills_files as $item)
                                <div class="col-sm-2 mb-3 file-card" id="file-{{ $item->id }}">
                                    <div class="border p-2 rounded">
                                        <p class="mb-1"><strong>File Name</strong>: {{ $item->custom_title }}</p>


                                        <a href="{{ asset('bill-files/' . $item->stored_name) }}" download
                                            class="btn btn-sm btn-success">
                                            <i class="fa fa-download"></i>
                                        </a>

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
                </tr>
                <tr>
                    <td style="vertical-align: text-top">
                        <p class="text-dark-grey">{!! !empty($purchaseBill->note) ? $purchaseBill->note : '--' !!}</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table>
                            <tr>
                                @if (isset($taxes))
                                    <p class="text-dark-grey">
                                        @if ($purchaseOrder->calculate_tax == 'after_discount')
                                            @lang('messages.calculateTaxAfterDiscount')
                                        @else
                                            @lang('messages.calculateTaxBeforeDiscount')
                                        @endif
                                    </p>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                <table>

        </div>
    </div>


</div>
<!-- BILL CARD END -->


<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>

<script>
    $('body').on('click', '.delete-bill', function() {
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
                            window.location.href = "{{ route('bills.index') }}";
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
</script>
