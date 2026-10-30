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
            td.text-end {
                text-align: end;
            }
        </style>

        <div class="card  invoice">
            <!-- CARD BODY START -->
            <div class="add-client bg-white rounded">
               

            <div class="card-body">
                <div>
                    <h5>{{__('app.project')}}: {{$projectDetails->project_name}}</h5>
                    <h5>{{__('app.subProject')}}: {{$projectDetails->task_info}}</h5>
            <div class="d-flex justify-content-end align-items-center">
    @if($projectDetails->status == 1 && $projectDetails->validate == 1)
        <span class="badge badge-success mr-2">Approved</span>
    @elseif($projectDetails->status == 2 && $projectDetails->validate == 2)
        <span class="badge badge-danger mr-2">Rejected</span>
    @elseif($approvalPermission == 'all')
       
        <button class="btn btn-success btn-decorative mr-2" data-toggle="modal" data-target="#confirmationModal" data-action="approve" data-budget-id="{{ $budgetId }}">
            <i class="fas fa-thumbs-up"></i> Approve
        </button>
        <button class="btn btn-danger btn-decorative" data-toggle="modal" data-target="#confirmationModal" data-action="reject" data-budget-id="{{ $budgetId }}">
            <i class="fas fa-thumbs-down"></i> Reject
        </button>
    @else
       <span class="badge badge-warning mr-2">Not-Validate</span>
    @endif
</div>




            </div>
                <br>
                    <table width="100%" class="inv-desc d-none d-lg-table d-md-table">
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <table class="inv-detail f-14 table-responsive-sm" width="100%">
                                        <tbody>
                                            <tr class="i-d-heading bg-light-grey text-dark-grey font-weight-bold">
                                                <td width="5%">Sr.No.</td>
                                                <td width="25%" class="border-right-0 border-left-0">WBS</td>
                                                <td width="45%" class="border-right-0">Budget Head Name</td>
                                                <td width="12.5%" class="border-left-0 text-end">Amount</td>
                                                <td width="12.5%" class="border-left-0 text-end">New Amount</td>
                                            </tr>
                    
                                            @php
                        $totalOld = 0;
                        $totalNew = 0;
                    @endphp
                    
                    @foreach ($budget_details as $index => $item)
                        @php
                            $totalOld += $item['old_val'];
                            $totalNew += $item['new_val'];
                        @endphp
                        <tr class="text-dark font-weight-semibold f-13">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item['wbs'] }}</td>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-end">
                                ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency($item['old_val']) }}
                            </td>
                            <td class="text-end">
                                ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency($item['new_val']) }}
                            </td>
                        </tr>
                    @endforeach
                    
                    <!-- Total Row -->
                    <tr class="text-dark font-weight-bold bg-light-grey">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end">
                            ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency($totalOld) }}
                        </td>
                        <td class="text-end">
                            ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency($totalNew) }}
                        </td>
                    </tr>
                    
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
    </tbody>
                    </table>
                    
                    
                    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
                                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to <span id="actionText"></span> this request?
                                    
                                    <!-- Show comment field for rejection -->
                                    <div id="commentSection" style="display:none;">
                                        <label for="comment">Please provide a reason for rejection:</label>
                                        <textarea id="comment" name="comment" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" id="confirmActionButton" class="btn btn-primary">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#confirmationModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var action = button.data('action');
        var budgetId = button.data('budget-id');
        var actionText = action === 'approve' ? 'Approve' : 'Reject';

        $('#actionText').text(actionText);
        $('#confirmActionButton').data('action', action);
        $('#confirmActionButton').data('budget-id', budgetId);

        $('#commentSection').toggle(action === 'reject');
        $('#comment').val('');
    });

    $('#confirmActionButton').click(function() {
        var action = $(this).data('action');
        var budgetId = $(this).data('budget-id');
        var comment = $('#comment').val().trim();

        if (action === 'reject' && comment === '') {
            alert('Please provide a comment for rejection.');
            return;
        }

        $.ajax({
            url: '{{ route("updateBudgetStatus") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                comment: comment,
                action: action,
                budgetId: budgetId
            },
            success: function(response) {
                console.log(response);
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else {
                    alert('Action completed successfully.');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('There was an error processing the request.');
                console.error(xhr.responseText);
            }
        });

        $('#confirmationModal').modal('hide');
    });
});

</script>
