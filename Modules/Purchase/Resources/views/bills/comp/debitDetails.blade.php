<div class="modal fade" id="debitDetails" tabindex="-1" aria-labelledby="debitDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="approvalForm" action="{{ route('update_debit_details') }}" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="debitDetailsLabel">{{ __('app.debitDetails') }}</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="bill_idx" name="bill_id">
                    <div class="row p-2">

                        <table class="table" id="htmlInstantDebitDetail">

                        </table>
                    </div>
                    <div class="row">
                        {{-- <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="instantDebitAmount"
                                    class="form-label">{{ __('app.instantDebitAmount') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="instantDebitAmount"
                                    name="instantDebitAmount">
                            </div>
                        </div> --}}
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="HoldBillAmt" class="form-label">{{ __('app.HoldBillAmt') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="HoldBillAmt"
                                    name="HoldBillAmt">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="AdvanceRecovery" class="form-label">{{ __('app.AdvanceRecovery') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="AdvanceRecovery"
                                    name="AdvanceRecovery">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="Steel" class="form-label">{{ __('app.Steel') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="Steel" name="Steel">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="Cement" class="form-label">{{ __('app.Cement') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="Cement" name="Cement">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="Electricity" class="form-label">{{ __('app.Electricity') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="Electricity"
                                    name="Electricity">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="OtherDebits" class="form-label">{{ __('app.OtherDebits') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="OtherDebits"
                                    name="OtherDebits">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="SafetyDebit" class="form-label">Safety Debit</label>
                                <input type="text" class="form-control height-35 f-15" id="SafetyDebit"
                                    name="SafetyDebit">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="QualityDebit" class="form-label">Quality Debit</label>
                                <input type="text" class="form-control height-35 f-15" id="QualityDebit"
                                    name="QualityDebit">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="Housekeeping" class="form-label">Housekeeping</label>
                                <input type="text" class="form-control height-35 f-15" id="Housekeeping"
                                    name="Housekeeping">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-3">
                                <label for="PoorPerformance" class="form-label">Poor Performance</label>
                                <input type="text" class="form-control height-35 f-15" id="PoorPerformance"
                                    name="PoorPerformance">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label for="stageDescription" class="form-label">{{ __('app.stageDescription') }}</label>
                                <textarea class="form-control" name="stageDescription" id="stageDescription" rows="2"></textarea>
                                <div class="d-block invalid-feedback" id="stageDescriptionFeedback">Maximum 14 words — 0/14</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label for="billDescription" class="form-label">{{ __('app.billDescription') }}</label>
                                <textarea class="form-control" name="billDescription" id="billDescription" rows="2"></textarea>
                                <div class="d-block invalid-feedback" id="billDescriptionFeedback">Maximum 14 words — 0/14</div>
                            </div>
                        </div>

                    </div>
                    <div id="allHoldContainer"></div>

                </div>
                <div>
                    <p class="text-center p-2" id="no_permission"></p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="smtBtn">Submit</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
