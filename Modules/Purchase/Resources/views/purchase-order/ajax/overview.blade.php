<style>
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
</style>
@if($order->billed_status != 'billed')
  {{--  <div class="d-flex align-content-center flex-lg-row-reverse mt-4">
        <x-forms.link-primary link="{{route('bills.create')}}?order={{$order->id}}" class="mr-3 float-left" icon="money-bill">
            @lang('purchase::modules.purchaseOrder.convertToBill')
        </x-forms.link-primary>
    </div> --}}
@endif
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
                            <!--<tr>-->
                            <!--    <td class="bg-light-grey border-right-0 f-w-500">-->
                            <!--        @lang('app.status')</td>-->
                            <!--    <td class="border-left-0">{{ $order->purchase_status }}-->
                            <!--    </td>-->
                            <!--</tr>-->
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
                        @lang('app.gstAmount') Amount
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
                                <td align="right">{{ $item->retention_amount }}%</td>
                                <td align="right">{{ $item->taxes_percent }}%</td>
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

@if ($approvePermission == 'all')
            
            <table class="inv-note">
                <tr>
                    <td height="30" colspan="2"></td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                        <a href="{{ route('purchase-work-order.approve', ['po_id' => $po_id]) }}" 
                           class="btn btn-primary btn-sm ms-2">
                            @lang('app.poWoApprove')
                        </a>
                    </td>
                </tr> 
                
            </table>
             @endif
        </div>
    </div>
    <!-- CARD BODY END -->
<!-- INVOICE CARD END -->
 <!-- CARD FOOTER START -->
<!-- <div class="card-footer bg-white border-0 d-flex justify-content-start py-0 py-lg-4 py-md-4 mb-4 mb-lg-3 mb-md-3">-->
<!--    <div class="d-flex">-->
<!--        <div class="inv-action mr-3 mr-lg-3 mr-md-3 dropup">-->
<!--            <button class="dropdown-toggle btn-primary" type="button" id="dropdownMenuButton"-->
<!--                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.action')-->
<!--                <span><i class="fa fa-chevron-up f-15"></i></span>-->
<!--            </button>-->
            <!-- DROPDOWN - INFORMATION -->
<!--            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" tabindex="0">-->
 
<!--                <li>-->
<!--                    <a class="dropdown-item f-14 text-dark"-->
<!--                        href="{{ route('purchase_order.download', [$order->id]) }}">-->
<!--                        <i class="fa fa-download f-w-500 mr-2 f-11"></i> @lang('app.download')-->
<!--                    </a>-->
<!--                </li>-->
 
<!--            </ul>-->
<!--        </div>-->

<!--        <x-forms.button-cancel :link="route('purchase-order.index')" class="border-0 mr-3">@lang('app.cancel')-->
<!--        </x-forms.button-cancel>-->
<!--    </div>-->
<!--</div>-->


</div>
<!-- CARD FOOTER END -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>

<script>
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

    $('body').on('click', '.delete-order', function() {
        var id = $(this).data('order-id');
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
                url = url.replace(':id', id);

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
