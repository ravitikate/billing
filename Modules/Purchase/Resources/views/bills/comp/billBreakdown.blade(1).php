<div class="modal fade" id="billBreakdown" tabindex="-1" aria-labelledby="billBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="billBreakdownLabel">{{ __('app.billBreakdown') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <style>
                td strong {
                    font-size: 10px;
                    text-transform: uppercase
                }
            </style>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td><strong>Label</strong></td>
                            <td><strong>Amount</strong></td>
                        </tr>
                        <tr>
                            <td><strong>CERTIFIED BILL:</strong></td>
                            <td><span id="currentsub_total"></span></td>
                        </tr>
                        <tr>
                            <td><strong>CGST + SGST</strong></td>
                            <td><span id="currenttax_total"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Release Hold Amount</strong></td>
                            <td><span id="releaseHoldAmt"></span></td>
                        </tr>
                        <tr>
                            <td><strong>GROSS CERTIFIED (A):</strong></td>
                            <td><span id="currenttotal"></span></td>
                        </tr>
                        <tr>
                            <td colspan="2"><br></td>
                        </tr>
                        <tr>
                            <td><strong>Advance Recovery</strong></td>
                            <td><span id="advancerecovery"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Hold Bill Amount</strong></td>
                            <td><span id="holdbillamt"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Retention Cost</strong></td>
                            <td><span id="currentretention_cost"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Total Recoveries(B)</strong></td>
                            <td><span id="totalrecoveries"></span></td>
                        </tr>

                        <tr>
                            <td colspan="2"><br></td>
                        </tr>
                        <tr>
                            <td><strong>Steel</strong></td>
                            <td><span id="steel"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Cement</strong></td>
                            <td><span id="cement"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Electricity</strong></td>
                            <td><span id="electricity"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Safety Debit</strong></td>
                            <td><span id="safetydebit"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Quality Debit</strong></td>
                            <td><span id="qualitydebit"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Housekeeping</strong></td>
                            <td><span id="housekeeping"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Poor Performance</strong></td>
                            <td><span id="poorperformance"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Other Debits</strong></td>
                            <td><span id="otherdebit"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Instant Debit</strong></td>
                            <td><span id="instantdebit"></span></td>
                        </tr>
                        <tr>
                            <td><strong>TOTAL DEBIT AMOUNT (C):</strong></td>
                            <td><span id="totaldebitamount"></span></td>
                        </tr>
                        <tr>
                            <td colspan="2"><br></td>
                        </tr>
                        <tr>
                            <td><strong>Net Payable Amount (A-B-C)</strong></td>
                            <td><span id="netpayableamount"></span></td>
                        </tr>

                    </table>

                </div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
