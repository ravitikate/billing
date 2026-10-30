@php
    $addPermission = user()->permission('add_vendor_payment');

@endphp
<style>
    input#currentsub_total_input,
    input#currenttotal_input,
    input#totalrecoveries_input,
    input#totaldebitamount_input,
    input#netpayableamount_input,
    input#currentretention_cost_input,
    input#currenttax_total_input,
    input#instantdebit_input,
    input#releaseHoldAmt_input {
        border: 0 !important;
        background: transparent;
    }
</style>
<div class="modal fade" id="billBreakdown" tabindex="-1" aria-labelledby="billBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="billBreakdownLabel">
                      @if (isset($bills)) 
                    {{ __('app.amended_bill_details') }}
                    @else 
                    {{ __('app.billBreakdown') }}
                    @endif
                    </h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <style>
                td strong {
                    font-size: 10px;
                    text-transform: uppercase
                }
            </style>
            <form action="{{ route('update_debit_details_by_accountant') }}" method="POST" >
                @csrf

            <div class="modal-body">
                <input type="hidden" id="bill_id" name="bill_id" value="">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td><strong>Label</strong></td>
                            <td><strong>Amount</strong></td>
@if(isset($bills))
                            @if ($addPermission == 'all' || $addPermission == 'added')
                                <td><strong>Updated Amount</strong></td>
                            @endif
                            @endif

                        </tr>

                        @php
                            $readonlyIds = [
                                'currentsub_total',
                                'currenttotal',
                                'totalrecoveries',
                                'totaldebitamount',
                                'netpayableamount',
                                'currentretention_cost',
                                'currenttax_total',
                                'releaseHoldAmt',
                                'instantdebit'
                            ];
                            $rowsMain = [
                                ['label' => 'CERTIFIED BILL:', 'id' => 'currentsub_total'],
                                ['label' => 'CGST + SGST', 'id' => 'currenttax_total'],
                                ['label' => 'Release Hold Amount', 'id' => 'releaseHoldAmt'],
                                ['label' => 'GROSS CERTIFIED (A):', 'id' => 'currenttotal'], // total → no input
                            ];

                            $rowsRecovery = [
                                ['label' => 'Advance Recovery', 'id' => 'advancerecovery'],
                                ['label' => 'Hold Bill Amount', 'id' => 'holdbillamt'],
                                ['label' => 'Retention Cost', 'id' => 'currentretention_cost'],
                                ['label' => 'Total Recoveries(B)', 'id' => 'totalrecoveries'], // total → no input
                            ];

                            $rowsDebit = [
                                ['label' => 'Steel', 'id' => 'steel'],
                                ['label' => 'Cement', 'id' => 'cement'],
                                ['label' => 'Electricity', 'id' => 'electricity'],
                                ['label' => 'Safety Debit', 'id' => 'safetydebit'],
                                ['label' => 'Quality Debit', 'id' => 'qualitydebit'],
                                ['label' => 'Housekeeping', 'id' => 'housekeeping'],
                                ['label' => 'Poor Performance', 'id' => 'poorperformance'],
                                ['label' => 'Other Debits', 'id' => 'otherdebit'],
                                ['label' => 'Instant Debit', 'id' => 'instantdebit'],
                                ['label' => 'TOTAL DEBIT AMOUNT (C):', 'id' => 'totaldebitamount'], // total → no input
                            ];
                        @endphp

                        {{-- Main rows --}}
                        @foreach ($rowsMain as $row)
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td><span id="{{ $row['id'] }}"></span></td>
                                @if(isset($bills))
                                @if ($addPermission == 'all' || $addPermission == 'added')
                                    <td>
                                        <input type="text" id="{{ $row['id'] }}_input" name="{{ $row['id'] }}_input"
                                            {{ in_array($row['id'], $readonlyIds) ? 'readonly' : '' }} oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                    </td>
                                @endif
                                @endif
                            </tr>
                        @endforeach

                        <tr>
                            <td colspan="{{ $addPermission == 'all' || $addPermission == 'added' ? 3 : 2 }}"><br></td>
                        </tr>

                        {{-- Recovery rows --}}
                        @foreach ($rowsRecovery as $row)
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td><span id="{{ $row['id'] }}"></span></td>
                                @if(isset($bills))
                                @if ($addPermission == 'all' || $addPermission == 'added')
                                    <td>
                                        <input type="text" id="{{ $row['id'] }}_input" name="{{ $row['id'] }}_input"
                                            {{ in_array($row['id'], $readonlyIds) ? 'readonly' : '' }} oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                    </td>
                                @endif
                                @endif
                            </tr>
                        @endforeach

                        <tr>
                            <td colspan="{{ $addPermission == 'all' || $addPermission == 'added' ? 3 : 2 }}"><br></td>
                        </tr>

                        {{-- Debit rows --}}
                        @foreach ($rowsDebit as $row)
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td><span id="{{ $row['id'] }}"></span></td>
                                @if(isset($bills))

                                @if ($addPermission == 'all' || $addPermission == 'added')
                                    <td>
                                        <input type="text"  id="{{ $row['id'] }}_input" name="{{ $row['id'] }}_input"
                                            {{ in_array($row['id'], $readonlyIds) ? 'readonly' : '' }} oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                                    </td>
                                @endif
                                @endif
                            </tr>
                        @endforeach

                        <tr>
                            <td colspan="{{ $addPermission == 'all' || $addPermission == 'added' ? 3 : 2 }}"><br></td>
                        </tr>

                        {{-- Final --}}
                        <tr>
                            <td><strong>Net Payable Amount (A-B-C)</strong></td>
                            <td><span id="netpayableamount"></span></td>
                                @if(isset($bills))

                            @if ($addPermission == 'all' || $addPermission == 'added')
                                <td>
                                    <input type="text" id="netpayableamount_input" name="netpayableamount_input" readonly>
                                </td>
                            @endif
                            @endif
                        </tr>
                    </table>



                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    <span id="form_submitButton"></span>


            </div>
            </form>
        </div>
    </div>
</div>
