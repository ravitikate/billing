@extends('layouts.app')

@section('content')

    @php
        $addContractPermission = user()->permission('renew_contract');
    @endphp
    <style>
        .FlexInnerBox {
            display: flex;
            gap: 30px;
            margin: 30px;
        }

        .flexHead {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cardContent {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        img.invImg {
            width: 60px;
            border-radius: 10px;
        }
    </style>

    <div class="content-wrapper border-top-0 client-detail-wrapper">

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="card-header bg-white border-0  d-flex justify-content-between pt-4 pb-4">
                <h4 class="f-18 f-w-500 mb-0">Invoice item history of <em>{{ $invoice_items->item_name }}</em> in
                    {{ $invoice_items->purchase_bill_number }}</h4>
            </div>
            <div class="invoice-details">
                {{-- {{ dd($invoice_items)}} --}}
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Item Name : {{ $invoice_items->item_name }} <br>
                                Department : {{ $department->team_name }} <br>
                                Quantity : {{ $invoice_items->quantity }}
                                <br>
                                Cost Per Item : {{ $invoice_items->unit_price }}
                                <br>
                                Total Amount : {{ $invoice_items->amount }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <div class="FlexBox" id="comment-list">
                @if (isset($invoiceItems) && count($invoiceItems) > 0)
                    @foreach ($invoiceItems as $item)
                        {{-- @php
                    $firstCreatedDate = DB::table('approval_items_by')
                                          ->where('invoice_item_id', $item->invoice_item_id)
                                          ->first();
                    $priorityTime = DB::table('priority_role')
                                      ->where('user_id', $item->user_id)
                                      ->where('project_id', $invoice_items->project_id)
                                      ->first();

                    if ($firstCreatedDate && $priorityTime) {
                        $createdAt = $firstCreatedDate->created_at;
                        $approvalTime = $priorityTime->approval_time;
                        $approvalUnit = $priorityTime->approval_unit;

                        $createdDateTime = \Carbon\Carbon::parse($createdAt);

                        // Calculate approval time
                        if ($approvalUnit === 'hours') {
                            $approvalDateTime = $createdDateTime->copy()->addHours($approvalTime);
                        } elseif ($approvalUnit === 'day') {
                            $approvalDateTime = $createdDateTime->copy()->addDays($approvalTime);
                        } else {
                            $approvalDateTime = $createdDateTime;
                        }

                        // Get duration from created date to approval due date
                        $duration = $createdDateTime->diffForHumans($approvalDateTime, true);

                        echo "Approval is due from $createdDateTime to $approvalDateTime ($duration)";
                    } else {
                        echo "Approval information not available.";
                    }
                @endphp --}}

                        <div class="FlexInnerBox">
                            <div class="cardImage">
                                <a href="{{ url('/account/employees/' . $item->user_id) }}">
                                    @if ($item->image)
                                        <img src="{{ url('user-uploads/avatar' . $item->image) }}" alt="Vertscend"
                                            class="invImg">
                                    @else
                                        <img src="{{ url('img/gravatar.png') }}" alt="Vertscend" class="invImg">
                                    @endif
                                </a>
                            </div>
                            <div class="cardContent">
                                <div class="col80">
                                    <div class="flexHead">
                                        <h6 class="card-title f-15 f-w-500 mb-0"> {{ $item->name }}</h6>
                                        <p class="card-date f-11 text-lightest mb-0">
                                            @if ($item->status_name == 'Approve')
                                                <i style="color: green">( {{ $item->status_name }} )</i>
                                            @else
                                                <i style="color: red"> ( {{ $item->status_name }} )</i>
                                            @endif
                                            <span>
                                                {{ \Carbon\Carbon::parse($item->created_at)->format('d-M-Y h:i A') }}</span>
                                        </p>
                                    </div>

                                    <div class="flexContent ">
                                        <!--(<span> Status Change Date --->
                                        <!--     -->
                                        <!--</span>)-->
                                        <p style="mb-0"><strong>Note :- </strong>{{ $item->message }}</p>
                                    </div>
                                </div>
                                <div class="col20">
                                    @if (!empty($item->uploaded_file_name))
                                        <a href="{{ asset('storage/' . $item->uploaded_file_name) }}" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fa fa-download"></i>
                                        </a>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="FlexInnerBox">
                        <p>No invoice items found.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
