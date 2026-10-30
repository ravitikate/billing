<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@lang('purchase::app.menu.purchaseOrder') - {{ $order->purchase_order_number }}</title>
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ $company->favicon_url }}">
    <meta name="theme-color" content="#ffffff">
    @includeIf('invoices.pdf.invoice_pdf_css')
    <style>
        .bg-grey {
            background-color: #F2F4F7;
        }

        .bg-white {
            background-color: #fff;
        }

        .border-radius-25 {
            border-radius: 0.25rem;
        }

        .p-25 {
            padding: 1.25rem;
        }

        .f-11 {
            font-size: 11px;
        }

        .f-12 {
            font-size: 12px;
        }

        .f-13 {
            font-size: 13px;
        }

        .f-14 {
            font-size: 13px;
        }

        .f-15 {
            font-size: 13px;
        }

        .f-21 {
            font-size: 17px;
        }

        .text-black {
            color: #28313c;
        }

        .text-grey {
            color: #616e80;
        }

        .font-weight-700 {
            font-weight: 700;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        . {
            text-transform: capitalize;
        }

        .line-height {
            line-height: 15px;
        }

        .mt-1 {
            margin-top: 1rem;
        }

        .mb-0 {
            margin-bottom: 0px;
        }

        .b-collapse {
            border-collapse: collapse;
        }

        .heading-table-left {
            padding: 6px;
            border: 1px solid #DBDBDB;
            font-weight: bold;
            background-color: #f1f1f3;
            border-right: 0;
        }

        .heading-table-right {
            padding: 6px;
            border: 1px solid #DBDBDB;
            border-left: 0;
        }

        .unpaid {
            color: #d30000;
            border: 1px solid #d30000;
            position: relative;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 0.25rem;
            width: 100px;
            text-align: center;
            margin-top: 50px;
        }

        .other {
            color: #000000;
            border: 1px solid #000000;
            position: relative;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 0.25rem;
            width: 120px;
            text-align: center;
            margin-top: 50px;
        }

        .paid {
            color: #28a745 !important;
            border: 1px solid #28a745;
            position: relative;
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 0.25rem;
            width: 100px;
            text-align: center;
            margin-top: 50px;
        }

        .main-table-heading {
            border: 1px solid #DBDBDB;
            background-color: #f1f1f3;
            font-weight: 700;
        }

        .main-table-heading td {
            padding: 5px 8px;
            border: 1px solid #DBDBDB;
            font-size: 13px;
        }

        .main-table-items td {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
        }

        .total-box {
            border: 1px solid #e7e9eb;
            padding: 0px;
            border-bottom: 0px;
        }

        .subtotal {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .subtotal-amt {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .total {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            font-weight: 700;
            border-left: 0;
            border-right: 0;
        }

        .total-amt {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
            font-weight: 700;
        }

        .balance {
            font-size: 14px;
            font-weight: bold;
            background-color: #f1f1f3;
        }

        .balance-left {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .balance-right {
            padding: 5px 8px;
            border: 1px solid #e7e9eb;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .centered {
            margin: 0 auto;
        }

        .rightaligned {
            margin-right: 0;
            margin-left: auto;
        }

        .leftaligned {
            margin-left: 0;
            margin-right: auto;
        }

        .page_break {
            page-break-before: always;
        }

        #logo {
            height: 50px;
        }

        .word-break {
            max-width: 175px;
            word-wrap: break-word;
        }

        .summary {
            padding: 11px 10px;
            border: 1px solid #e7e9eb;
            font-size: 11px;
        }

        .border-left-0 {
            border-left: 0 !important;
        }

        .border-right-0 {
            border-right: 0 !important;
        }

        .border-top-0 {
            border-top: 0 !important;
        }

        .border-bottom-0 {
            border-bottom: 0 !important;
        }
        .h3-border {
            border-bottom: 1px solid #AAAAAA;
        }
       
</style>
    @if($invoiceSetting->locale == 'th')
    <style>

            table td {
            font-weight: bold !important;
            font-size: 20px !important;
        }

        .description {
            font-weight: bold !important;
            font-size: 16px !important;
        }


    </style>
@endif

</head>

<body class="content-wrapper">
     <table  class="bg-white" class="bg-white" cellpadding="5" cellspacing="0" width="100%" border="1" >
        <tr>
            <td style="width: 15%;"><img src="{{url('img/logo/logo.jpeg')}}" alt="{{ mb_ucwords($company->company_name) }}"
                     width="100" height="100px"/></td>
            <td style="width: 70%; text-align: center;">
                <h2 style="
                    padding: 0;
                    margin: 0;
                    font-size: 30px;
                " class="f-21 text-black font-weight-200 text-uppercase">Thanish Construction LLP</h2>
                                <p style="
                    margin: 0;
                    line-height: 1.5;
                    font-size: 14px;
                ">Office No.601, Gagan UNO,603-609 Dhole Patil Road, 411001, Maharashtra, IN</p>
                                <p style="
                    margin: 0;
                    line-height: 1.5;
                    font-size: 14px;
                ">PAN No. AAOFT9919K. GSTIN No. 27AAOFT9919K1Z8</p>
            </td>
            <td style="width: 15%;text-align: right;"><img src="{{ $invoiceSetting->logo_url }}" alt="{{ mb_ucwords($company->company_name) }}"
                    id="logo" /></td>
        </tr>
    </table>
    <br>
    <table  class="bg-white" cellpadding="5" cellspacing="0" width="100%" border="1">

        <tr>
            <th colspan="2">
            @if($order->order_type == 'purchase_order')
                Purchase Order
            @elseif($order->order_type == 'work_order')
                Work Order
            @else
                ''
            @endif
        </th>

        </tr>
        <tr>
            <td><strong> @if($order->order_type == 'purchase_order')
                Purchase 
            @elseif($order->order_type == 'work_order')
                Work 
            @else
                ''
            @endif Order No:</strong> {{ $order->po_number }}</td>
                    <td style="width:50%"><strong> @if($order->order_type == 'purchase_order')
                PO
            @elseif($order->order_type == 'work_order')
                WO
            @else
                ''
            @endif Date:</strong> {{ $order->purchase_date->translatedFormat($order->company->date_format) }}</td>
        </tr>
        <tr>
            <td><strong>Delivery Date:</strong> {{ $order->expected_delivery_date->translatedFormat($order->company->date_format) }}</td>
            <td><strong>Indent Number:</strong> {{$order->reference_number}}</td>
        </tr>
        <tr>
            <td><strong>Project Name:</strong> 
            @php
            $result = DB::table('projects as p')
                    ->join('tasks as t', 't.project_id', '=', 'p.id')
                    ->select('p.project_name', 'p.project_short_code', 't.task_short_code', 't.heading', 't.id')
                    ->where('p.id', $order->project_id)
                    ->where('t.id', $order->subproject_id)
                    ->first();
                    @endphp
            {{ $result->project_name .' / '. $result->heading}}</td>
            <td><strong>Indent Date:</strong></td>
        </tr>
        <!--<tr>-->
        <!--    <td><strong>Start Date:</strong></td>-->

        <!--    <td><strong>End Date:</strong></td>-->

        <!--</tr>-->
        <tr>
            <td><strong>Supplier Name & Address</strong></td>
            <td><strong>Delivery Address</strong></td>
        </tr>
        <tr>
            <td >
                 
                                @lang('modules.invoices.billedTo'),<br>
                        {{ mb_ucwords(company()->company_name) }}<br>
                        @if ($order->address)
                            {!! nl2br($order->address->address) !!}<br>
                        @endif
                        
                        @if ($invoiceSetting->show_gst == 'yes' && $order->address)
                            {{ strtoupper($order->address->tax_name) }}: {{ $order->address->tax_number }}<br>
                        @endif
                      <strong>Contact Person:</strong>{{ company()->company_name }}  <br>
                <strong>Mobile No:</strong> {{ company()->company_phone }}<br>
                <strong>Email:</strong> @if ( company()->company_email)
                        {{company()->company_email}}<br>
                    @endif
               
            </td>
            <td>
               
                    @lang('modules.invoices.billedFrom'),<br>
                    
                   
                    @if ($order->vendor && $order->vendor->billing_address)
                        {{$order->vendor->billing_address}}<br>
                    @endif
                   
                    <strong>Contact Person:</strong> @if ($order->vendor && $order->vendor->primary_name)
                        {{$order->vendor->primary_name}}
                    @endif<br>
                <strong>Mobile No:</strong>  @if ($order->vendor && $order->vendor->phone)
                        {{$order->vendor->phone}}
                    @endif<br>
                <strong>Email:</strong>  @if ($order->vendor && $order->vendor->email)
                        {{$order->vendor->email}}<br>
                    @endif
                    
               
            </td>
        </tr>
       
    </table>
   
    <table width="100%" class="f-14 b-collapse">
    <tr>
        <td height="10" colspan="10"></td>
    </tr>

    <!-- Table Heading -->
    <tr class="main-table-heading text-grey">
        <td style="width: 2%;" class="border-right-0 border-left-0">@lang('app.sr')</td>
        <td style="width: 17%;" class="border-right-0 border-left-0">@lang('app.wbs')</td>
        <td style="width: 33%;" class="border-right-0">@lang('app.description')</td>
        <td style="width: 5%;" class="border-right-0 border-left-0" align="right">@lang('modules.invoices.qty')</td>
        <td style="width: 5%;" class="border-right-0 border-left-0" align="right">@lang('modules.invoices.unitPrice') ({{ $order->currency->currency_code }})</td>
        <td style="width: 5%;" class="border-right-0 border-left-0" align="right">Reten<br><span style="text-transform: lowercase;">tion%</span></td>
        <td style="width: 5%;" class="border-right-0 border-left-0" align="right">@lang('modules.invoices.tax')</td>
        <td style="width: 9%;" class="border-right-0 border-left-0" align="right">@lang('app.gstAmount')</td>
        <td style="width: 9%;" class="border-left-0" align="right">@lang('modules.invoices.amount') ({{ $order->currency->currency_code }})</td>
        <td style="width: 10%;" class="border-left-0" align="right">@lang('modules.invoices.total') ({{ $order->currency->currency_code }})</td>
    </tr>

    <!-- Table Body -->
    @foreach ($order->items as $item)
        @if ($item->type == 'item')
            <tr class="f-12 main-table-items text-black">
                <td align="right">{{ $loop->iteration }}</td>
                <td align="left">{{ $item->wbs }}</td>
                <td>{{ $item->item_name }}</td>

                @if($item->quantity <= 0)
                    <td colspan="7" align="center">—</td>
                @else
                    <td align="right">
                        {{ $item->quantity }}
                        @if($item->unit)
                            <br><span class="f-11 text-dark-grey">{{ $item->unit->unit_type }}</span>
                        @endif
                    </td>
                    <td align="right">{{ currency_format($item->unit_price, $order->currency_id, false) }}</td>
                    <td align="right">{{ $item->retention_amount }}%</td>
                    <td align="right">{{ $item->taxes_percent }}%</td>
                    <td align="right">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->gst_amount)) }}</td>
                    <td align="right">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->amount)) }}</td>
                    <td align="right">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item->amount + $item->gst_amount)) }}</td>
                @endif
            </tr>
        @endif
    @endforeach

    <!-- Table Footer -->
    <tr class="f-12 main-table-items text-black">
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


    <table class="bg-white" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
        <tbody>
        <!-- Table Row Start -->
        @if ($order->note != '')
            <tr>
                <td height="10"></td>
            </tr>
            <tr>
                <td class="f-11">@lang('app.note')</td>
            </tr>
            <!-- Table Row End -->
            <!-- Table Row Start -->
            <tr class="text-grey">
                <td class="f-11 line-height word-break">{!! $order->note ? nl2br($order->note) : '--' !!}</td>
            </tr>
        @endif
        <tr>
            <td height="10"></td>
        </tr>
        {{-- <tr>
            <td class="f-11">
                @lang('modules.invoiceSettings.invoiceTerms')</td>
        </tr> --}}
        <!-- Table Row End -->

        @if (isset($taxes) && $invoiceSetting->tax_calculation_msg == 1)
            <!-- Table Row Start -->
            <tr class="text-grey">
                <td width="100%" class="f-11 line-height">
                    <p class="text-dark-grey">
                        @if ($order->calculate_tax == 'after_discount')
                            @lang('messages.calculateTaxAfterDiscount')
                        @else
                            @lang('messages.calculateTaxBeforeDiscount')
                        @endif
                    </p>
                </td>
            </tr>
            <!-- Table Row End -->
        @endif
        <!-- Table Row End -->
        </tbody>
    </table>

    <p>
        <div style="margin-top: 10px;" class="f-11 line-height text-grey">
            <b>@lang('modules.invoiceSettings.invoiceTerms')</b><br>{!! nl2br($orderSetting->purchase_terms) !!}
        </div>
    </p>
    
     
</body> 
</html>
