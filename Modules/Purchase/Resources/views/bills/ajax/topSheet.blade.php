<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-3 py-3 px-lg-4 px-md-4">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('app.topSheet')</h4>
    </div>
    <hr class="m-0 border-top-grey">
    <style>
        .table td {
            border: 1px solid #dee2e638 !important;
            width: 25%;
        }

        .new-table td {
            border: 1px solid #dee2e638 !important;
            width: 20%;
        }

        td strong {
            text-transform: uppercase;
            font-size: 11px;
        }

        img.signature {
            width: 100px;
            height: 70px;
            object-fit: contain;
        }
    </style>

    @php
        function numberToRupeesWords($num)
        {
            $num = str_replace(',', '', $num); // remove commas
            $isNegative = false;

            if ($num < 0) {
                $isNegative = true;
                $num = abs($num);
            }

            $ones = [
                '',
                'One',
                'Two',
                'Three',
                'Four',
                'Five',
                'Six',
                'Seven',
                'Eight',
                'Nine',
                'Ten',
                'Eleven',
                'Twelve',
                'Thirteen',
                'Fourteen',
                'Fifteen',
                'Sixteen',
                'Seventeen',
                'Eighteen',
                'Nineteen',
            ];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if ($num == 0) {
                return 'Zero Rupees';
            }

            $words = '';
            $decimalPart = '';

            if (strpos($num, '.') !== false) {
                [$integerPart, $decimalPart] = explode('.', (string) $num);
                $decimalPart = substr(str_pad($decimalPart, 2, '0'), 0, 2); // 2 digits only
            } else {
                $integerPart = (string) $num;
            }

            $integerPart = (int) $integerPart;

            // Indian number system
            if ($integerPart >= 10000000) {
                $words .= numberToWords((int) ($integerPart / 10000000), $ones, $tens) . ' Crore ';
                $integerPart %= 10000000;
            }

            if ($integerPart >= 100000) {
                $words .= numberToWords((int) ($integerPart / 100000), $ones, $tens) . ' Lakh ';
                $integerPart %= 100000;
            }

            if ($integerPart >= 1000) {
                $words .= numberToWords((int) ($integerPart / 1000), $ones, $tens) . ' Thousand ';
                $integerPart %= 1000;
            }

            if ($integerPart >= 100) {
                $words .= $ones[(int) ($integerPart / 100)] . ' Hundred ';
                $integerPart %= 100;
            }

            if ($integerPart > 0) {
                $words .= numberToWords($integerPart, $ones, $tens);
            }

            // Handle decimal part if it exists and is non-zero
            if (!empty($decimalPart) && (int) $decimalPart > 0) {
                $decimalWords = convertDecimalToWords($decimalPart);
                $words .= ' and ' . $decimalWords . ' Paisa';
            }

            $finalWords = trim($words) . ' Rupees';

            if ($isNegative) {
                $finalWords = 'Negative ' . $finalWords;
            }

            return $finalWords;
        }

        function numberToWords($num, $ones, $tens)
        {
            if ($num < 20) {
                return $ones[$num];
            } else {
                $tensPart = (int) ($num / 10);
                $onesPart = $num % 10;
                return $tens[$tensPart] . ($onesPart ? ' ' . $ones[$onesPart] : '');
            }
        }

        function convertDecimalToWords($decimal)
        {
            $ones = [
                '',
                'One',
                'Two',
                'Three',
                'Four',
                'Five',
                'Six',
                'Seven',
                'Eight',
                'Nine',
                'Ten',
                'Eleven',
                'Twelve',
                'Thirteen',
                'Fourteen',
                'Fifteen',
                'Sixteen',
                'Seventeen',
                'Eighteen',
                'Nineteen',
            ];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            $decimalNum = (int) $decimal;

            if ($decimalNum < 20) {
                return $ones[$decimalNum];
            }

            $tensPart = (int) ($decimalNum / 10);
            $onesPart = $decimalNum % 10;

            return $tens[$tensPart] . ($onesPart > 0 ? ' ' . $ones[$onesPart] : '');
        }

    @endphp
    <div class="table-container">
        <table class="table table-hover border-0 w-100">

            <tr>
                <td><strong>TRADE (FIRM) NAME:</strong></td>
                <td>{{ $company->company_name }}</td>
                <td><strong>NAME OF PROJECT:</strong></td>
                <td>{{ $project_details->project_name }}</td>
            </tr>
            <tr>
                <td><strong>GSTIN of Client:</strong></td>
                <td>{{ $company->gstin ?? '--' }}</td>

                <td><strong>{{ $po_details->category }} Firm Name:</strong></td>
                <td>{{ $vendor_details->primary_name }}</td>
            </tr>
            <tr>
                <td><strong>TYPE OF PAYMENT:</strong></td>
                <td>{{ $po_details->category }}</td>
                <td><strong>GSTIN of {{ $po_details->category }}:</strong></td>
                <td>{{ $vendor_details->gstn ?? '--' }}</td>
            </tr>
            <tr>
                <td><strong>Sub Project:</strong></td>
                <td>{{ $sub_project->task_short_code . '-' . $sub_project->heading }}</td>
                <td><strong>PAN of {{ $po_details->category }}:</strong></td>
                <td>{{ $vendor_details->pan ?? '--' }}</td>
            </tr>
            <tr>
                <td><strong>Work Category:</strong></td>
                <td>
                    @if ($po_details->order_type == 'purchase_order')
                        Purchase Order
                    @elseif ($po_details->order_type == 'work_order')
                        Work Order
                    @else
                    @endif
                </td>
                <td><strong>{{ $po_details->category }}'s Invoice Number:</strong></td>
                <td>{{ $bill_detail->reference_bill_no ? $bill_detail->reference_bill_no . ' - ' : '' }}
                    Bill#{{ sprintf('%03d', $bill_detail->purchase_bill_number) }}</td>
            </tr>
            <tr>
                <td><strong>RA Bill No:</strong></td>
                <td>
                    @php
                        $bills = DB::table('purchase_bills')->where('purchase_order_id', $po_details->id)->get();

                        // Find the index (0-based)
                        $index = $bills->search(function ($bill) use ($bill_detail) {
                            return $bill->id == $bill_detail->purchase_bill_number;
                        });

                        // Convert to 1-based position, or show '-' if not found
                        $position = $index !== false ? $index + 1 : '-';
                    @endphp
                    {{ $position }}
                </td>

                <td><strong>{{ $po_details->category }}'s Invoice Date:</strong></td>
                <td>
                    @php
                        $timestamp = $bill_detail->created_at; // Your timestamp
                        $date = new DateTime($timestamp);
                        $formattedDate = $date->format('d F Y');
                    @endphp
                    {{ $formattedDate }}</td>
            </tr>
            <tr>
                <td><strong>Bill Certification Date:</strong></td>
                <td>@php
                    $timestamp = $bill_detail->created_at; // Your timestamp
                    $date = new DateTime($timestamp);
                    $formattedDate = $date->format('d F Y');
                @endphp
                    {{ $formattedDate }}</td>
                <td></td>
                <td></td>

            </tr>
            <tr style="background: rgb(152, 152, 152);">
                <td colspan="4">
                    <h6>Work Order Details:</h6>
                </td>
            </tr>
            <tr style="background: rgb(152, 152, 152);">
                <td><strong>W.O / LOI Order No:</strong></td>
                <td>{{ $po_details->po_number }}</td>
                <td><strong>WO Base Value:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($woBaseValue)) }} </td>
            </tr>
            <tr>
                <td><strong>Stage Description:</strong></td>
                <td>
                    <p class="describe">{{ $bill_detail->stageDescription }}</p>
                </td>
                <td><strong>Addition of Taxes:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($WOtotalGstAmt)) }}</td>
            </tr>
            <tr>
                <td><strong>Bill Description:</strong></td>
                <td>{{ $bill_detail->billDescription }}</td>
                <td><strong>WO Gross Value:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($woGrossValue)) }} </td>
            </tr>
            <tr style="background: rgb(152, 152, 152);">
                <td><strong>Bill Details:</strong></td>
                <td><strong>Upto Previous Bill:</strong></td>
                <td><strong>Current Bill:</strong></td>
                <td><strong>Cumulative Bill :</strong></td>
            </tr>


            <tr>
                <td><strong>Certified Bill:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($subTotal)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Currentsub_total)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Cumulativesub_total)) }}</td>

            </tr>
            <tr>
                <td><strong>CGST:</strong></td>

                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($taxTotal / 2)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Currenttax_total / 2)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Cumulativetax_total / 2)) }}</td>
            </tr>
            <tr>
                <td><strong>SGST :</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($taxTotal / 2)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Currenttax_total / 2)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Cumulativetax_total / 2)) }}</td>
            </tr>
            <tr>
                <td><strong>Release of Hold:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($po_releaseHoldAmt)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($releaseHoldAmt)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($com_po_releaseHoldAmt)) }}</td>


            </tr>
            <tr>
                <td><strong>Gross Certified (A):</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($grandTotal)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Currenttotal)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Cumulativetotal)) }}</td>

            </tr>

            <tr style="background: rgb(152, 152, 152);">
                <td><strong>Recovery of Advance & Retention:</strong></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><strong>Advance Recovery:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_AdvanceRecovery)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($AdvanceRecovery)) }}</td>

                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_AdvanceRecovery)) }}</td>

            </tr>
            <tr>
                <td><strong>Retention:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_retentioncost)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Currentretention_cost)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_retention_cost)) }}</td>
            </tr>
            <tr>
                <td><strong>Hold:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_HoldBillAmt)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($HoldBillAmt)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_HoldBillAmt)) }}</td>
            </tr>
            <tr>
                <td><strong>Total (B):</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_totalAmtrecoveandretainion)) }}
                </td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($totalAmtrecoveandretainion)) }}
                </td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($post_totalAmtrecoveandretainion)) }}
                </td>
            </tr>
            <tr style="background: rgb(152, 152, 152);">
                <td><strong>Debit Details:</strong></td>
                <td></td>
                <td></td>
                <td></td>

            </tr>
            <tr>
                <td><strong>Debit Reason:</strong></td>
                <td></td>
                <td></td>
                <td></td>

            </tr>
            <tr>
                <td><strong>Steel:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_Steel)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Steel)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_steel)) }}</td>
            </tr>
            <tr>
                <td><strong>Cement:</strong></td>

                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_Cement)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Cement)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_cement)) }}</td>

            </tr>
            <tr>
                <td><strong>Electricity:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_Electricity)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($Electricity)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_electricity)) }}</td>

            </tr>
            <tr>
                <td><strong>Other Debits:</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_otherDebits)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($OtherDebits)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($total_otherDebits)) }}</td>

            </tr>
            <tr>
                <td><strong>Total Debit Amount (C):</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($previous_TotalDebitAmount)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($TotalDebitAmount)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($post_TotalDebitAmount)) }}</td>
            </tr>
            <tr>
                <td><strong>Net Certified Amount (A-B-C):</strong></td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($NetPreviousTotal)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($NetPayableAmount)) }}</td>
                <td style="text-align:right;">
                    {{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($NetTotalAmount)) }}</td>


            </tr>
            <tr>
                <td><strong>Net Payable Amount:</strong></td>
                <td colspan="3">
                    <strong>{{ '₹ ' . \App\Helper\NumberFormat::formatIndianCurrency(round($NetPayableAmount)) }} - (
                        {{ numberToRupeesWords($NetPayableAmount) }} )</strong>
                </td>

            </tr>
            <tr>
                <td colspan="4"><strong>Note - Accounts Department To Check Statutory Requirement:</strong></td>

            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>






        </table>
        <table class="table new-table table-hover border-0 w-100">
            <tr>
                @foreach ($prUsers as $user)
                    <td style="width:20%"><strong>Checked By</strong></td>
                @endforeach

            </tr>

            <tr>
                @foreach ($prUsers as $user)
                    @php
                        $approvedUser = DB::table('users as u')
                            ->join('approval_bill_details as ab', 'u.id', '=', 'ab.user_id')
                            ->where('u.id', $user->user_id)
                            ->where('ab.bill_id', $bill_detail->id)
                            ->select('u.name', 'u.signature', 'ab.*')
                            ->orderByDesc('ab.id')
                            ->first();
                    @endphp

                    <td>
                        <div class="signature-container">
                            @if ($approvedUser)
                                @if ($approvedUser->bypass === 'bypass')
                                    --
                                @elseif ($approvedUser->approval_status === 3)
                                    --
                                @elseif ($approvedUser->approval_status === 2 && $approvedUser->bypass === 'regular')
                                    @if ($approvedUser->signature)
                                        <img src="{{ url('user-uploads/signature/' . $approvedUser->signature) }}"
                                            class="signature" alt="{{ $user->username }}"
                                            title="{{ $user->username }}">
                                    @else
                                        No Signature
                                    @endif
                                @endif
                            @else
                                --
                            @endif
                        </div>

                        {{ $user->username }}<br>
                        <strong><small>{{ $user->designation }}</small></strong>
                    </td>
                @endforeach
            </tr>


            <style>
                .signature-container {
                    height: 72px;
                }
            </style>
        </table>

    </div>
</div>
