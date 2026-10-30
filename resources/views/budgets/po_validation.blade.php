@extends('layouts.app')




@section('content')
{{-- {{dd($budget_details)}} --}}
    <div class="content-wrapper border-top-0 client-detail-wrapper">
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
            .btnAlign {
    display: flex;
    align-items: center;
    justify-content: end;
    margin-right: 20px;
        gap: 10px;
}
        </style>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS Bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

        <div class="card  invoice">
            <!-- CARD BODY START -->
            <div class="add-client bg-white rounded">


                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>{{__('app.project')}}: {{$projectDetails->project_name}}</h5>
                            <h5>{{__('app.subProject')}}: {{$projectDetails->task_info}}</h5>
                        </div>
                       <div class="col-md-6">
                           
                        
                 <div class="btnAlign">
                       @php
                    $status = $poValidation->status ?? false;
                            $validate = $poValidation->validate ?? false;
                        $totalRemaining = 0;
                        $hasNegativeRemaining = false;

                        foreach ($merged_data as $item) {
                            $remaining = round(
                                ($item['budget_cost']) - 
                                (($item['po_amount']) + ($item['current_po_amount']) ?? 0)
                            );
                        
                            $totalRemaining += $remaining;
                        
                            if ($remaining < 0) {
                                $hasNegativeRemaining = true;
                            }
                        }

                    @endphp
                         @if($status == 'true' && $validate == 'false')
                            @if (!$hasNegativeRemaining && $totalRemaining > 0)
                                <!-- Show Approve button only if no negative remaining and total > 0 -->
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                    data-action="approve" data-po-id="{{ $po_id }}">
                                    {{ __('app.approve') }}
                                </button>
                            @endif
                        
                            <!-- Always show Reject button -->
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#approveRejectModal"
                                data-action="reject" data-po-id="{{ $po_id }}">
                                {{ __('app.reject') }}
                            </button>
                        @elseif($status == 'true' && $validate == 'true')
                            <button type="button" class=" btn text-success border-success rounded f-12">
                                {{ __('app.approve') }}
                            </button>
                        @elseif($status == 'false' && $validate == 'false')
                            <button type="button" class="btn text-danger border-danger rounded f-12 ">
                                {{ __('app.reject') }}
                            </button>
                        @endif
                 </div>

                            
                        </div>

                    </div>
                    <br>
 
 


<!-- Modal -->
<div class="modal fade" id="approveRejectModal" tabindex="-1" aria-labelledby="approveRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Responsive modal size -->
        <div class="modal-content">
            <form id="approvalForm" method="POST" action="{{ route('purchase-work-order.approveReject') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approveRejectModalLabel">{{ __('app.confirmationWindow') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="po_id" name="po_id">
                    <input type="hidden" id="action" name="action">

                    <p id="confirmMessage" class="fw-bold text-danger"></p>

                    <div id="rejectCommentBox" class="mt-3 d-none">
                        <label for="reject_comment" class="form-label">{{ __('app.comment') }}</label>
                        <textarea class="form-control" name="reject_comment" id="reject_comment" rows="3" placeholder="Enter reason for rejection"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('app.submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('app.close') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>



                    <table width="100%" class="inv-desc d-none d-lg-table d-md-table">
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <table class="inv-detail f-14 table-responsive-sm" width="100%">
                                        <tbody>
                                             <tr class="i-d-heading bg-light-grey text-dark-grey font-weight-bold">
                                                <td class="border-right-0 border-left-0" width="20%"> {{__('app.wbs')}} </td>
                                                <td class="border-right-0" width="30%">{{__('app.budgetHeadName')}}</td>
                                                <td class="border-right-0" width="15%">{{__('app.budgetHeadAmt')}}</td>
                                                <td class="border-left-0" width="10%"> {{__('app.oldAmt')}}  </td>
                                                <td class="border-left-0" width="10%"> {{__('app.currentAmt')}}</td>
                                                <td class="border-left-0" width="15%"> {{__('app.remaningAmt')}}</td>
                                            </tr>
                                           @if ($merged_data->count() == 0)
                                            <tr>
                                                <td colspan="5" class="text-center text-dark-grey font-weight-bold">
                                                    {{ __('app.po_not_set') }}
                                                </td>
                                            </tr>
                                        @else
                                           @foreach ($merged_data as $item)
                                                <tr class="text-dark font-weight-semibold f-13">
                                                    <td>{{ $item['wbs'] ?? '-' }}</td>
                                                    <td>{{ $item['name'] ?? '-' }}</td>
                                                    <td align="right">
                                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['budget_cost'] ?? 0)) }}
                                                    </td>
                                                    <td align="right">
                                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round(($item['po_amount'] ?? 0) )) }}
                                                    </td>
                                                    <td align="right">
                                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round(($item['current_po_amount'] ?? 0) )) }}
                                                    </td>
                                                    @php
                                                        $remaining = round(
                                                            ($item['budget_cost']) - 
                                                            (($item['po_amount']) + 
                                                             ($item['current_po_amount']) ?? 0)
                                                        );
                                                        $color = $remaining >= 0 ? 'green' : 'red';
                                                    @endphp
                                                    
                                                    <td align="right" style="color: {{ $color }};">
                                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency($remaining) }}
                                                    </td> 
                                                </tr>
                                            @endforeach 
                                        @endif
 
                                        </tbody>
                                    </table>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

         

    </div>
    </div>
@endsection
 
<script> 
document.addEventListener('DOMContentLoaded', function () {
    const approveRejectModal = document.getElementById('approveRejectModal');

    approveRejectModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const poId = button.getAttribute('data-po-id');

        // Assign values
        document.getElementById('po_id').value = poId;
        document.getElementById('action').value = action;

        const messageEl = document.getElementById('confirmMessage');
        const rejectCommentBox = document.getElementById('rejectCommentBox');
        const rejectCommentTextarea = document.getElementById('reject_comment');

        if (action === 'approve') {
            messageEl.textContent = 'Are you sure you want to approve this item?';
            messageEl.classList.remove('text-danger');
            messageEl.classList.add('text-success');
            rejectCommentBox.classList.add('d-none');
            rejectCommentTextarea.removeAttribute('required');
        } else {
            messageEl.textContent = 'Are you sure you want to reject this item?';
            messageEl.classList.remove('text-success');
            messageEl.classList.add('text-danger');
            rejectCommentBox.classList.remove('d-none');
            rejectCommentTextarea.setAttribute('required', 'required');
            rejectCommentTextarea.focus(); // Optional: auto-focus textarea
        }
    });
});
 

</script>


