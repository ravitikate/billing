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
    <div class="card-header bg-white border-0 d-flex justify-content-between pt-4 pb-4">
        <h4 class="f-18 f-w-500 mb-0">
            Bill history of <em>Bill#{{ str_pad($purchase_billsdata->id, 3, '0', STR_PAD_LEFT) }}
</em>
        </h4>
    </div>

    <div class="invoice-details">
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <td>
                        <strong>Bill No:</strong> Bill#{{ str_pad($purchase_billsdata->id, 3, '0', STR_PAD_LEFT) }}
 <br>
                        <strong>{{ __('app.project_name') }}:</strong> {{ $purchase_billsdata->project_name }} <br>
                        <strong>{{ __('app.sub_project_name') }}:</strong> 
                        {{ $purchase_billsdata->task_short_code . '-' . $purchase_billsdata->heading }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

            <div class="FlexBox" id="comment-list">
                @if (isset($b_deatils) && count($b_deatils) > 0)
                    @foreach ($b_deatils as $item)
                        

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
                                         @if ($item->status_name == 'Approved')
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
                                    @if ($item->uploaded_file_name)
                                    <!--<a href="{{ asset('user-uploads/invoice-approval-files/' . $item->uploaded_file_name) }}" target="_blank"-->
                                      <a href="#"   class="btn btn-primary btn-sm"> <i class="fa fa-download"></i></a>
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
