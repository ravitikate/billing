@extends('layouts.app')
@php
    use Carbon\Carbon;
@endphp
@push('styles')

    @if (
        (!is_null($viewEventPermission) && $viewEventPermission != 'none') ||
            (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none') ||
            (!is_null($viewTaskPermission) && $viewTaskPermission != 'none') ||
            (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none') ||
            (!is_null($viewLeavePermission) && $viewLeavePermission != 'none'))
    @endif
  <style>
        .card-body.p-0.h-200 {
            min-height: 287px;
            padding: 10px !important;
        }

        .dt-layout-cell.dt-layout-start {
            display: none !important;
        }
    </style>
    
@endpush

@section('content')
    <div class="px-4 py-2 border-top-0 emp-dashboard">
        <div class="d-lg-flex d-md-flex d-block py-2 pb-2 align-items-center">
            <div>
                <h3 class="heading-h3 mb-0 f-21 font-weight-bold">@lang('app.welcome') {{ $user->name }}</h3>
            </div>
            <div class="ml-auto d-flex clock-in-out mb-3 mb-lg-0 mb-md-0 m mt-4 mt-lg-0 mt-md-0 justify-content-end">
                <p class="mb-0 text-lg-right text-md-right f-18 font-weight-bold text-dark-grey d-grid align-items-center">
                    <input type="hidden" id="current-latitude" name="current_latitude">
                    <input type="hidden" id="current-longitude" name="current_longitude">

                    <span id="dashboard-clock">{!! now()->timezone(company()->timezone)->translatedFormat(company()->time_format) .
                        '</span><span class="f-10 font-weight-normal">' .
                        now()->timezone(company()->timezone)->translatedFormat('l') .
                        '</span>' !!}


                </p>
                
            </div>
        </div>

        <div class="row mt-3">
            @if (count(array_intersect(['profile', 'shift_schedule', 'birthday', 'notices'], $activeWidgets)) > 0)
                <div class="col e-d-info-notices">
                    <div class="row">
                        @if (in_array('profile', $activeWidgets))
                            <div class="col-md-12">
                                <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                                    <a
                                        @if (!in_array('client', user_roles())) href="{{ route('employees.show', user()->id) }}" @endif>
                                        <div class="card-horizontal align-items-center">
                                            <div class="card-img">
                                                <img class="" src=" {{ $user->image_url }}" alt="Card image">
                                            </div>
                                             <div class="card-body border-0 pl-0">
                                                <h4 class="card-title text-dark f-18 f-w-500 mb-0">{{ $user->name }}</h4>
                                                <p class="f-14 font-weight-normal text-dark-grey mb-2">
                                                    </p>
                                                <p class="card-text f-12 text-lightest">  
                                                    </p>
                                            </div>
                                        </div>
                                    </a>

                                </div>
                            </div>
                        @endif



                    </div>
                </div>
            @endif

            <div class="col mb-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex flex-column justify-content-between h-100">
                    <div class="d-block text-capitalize">
                        {{-- Header Row: Title on left, Button on right --}}
                        <div class="d-flex justify-content-between align-items-center mb-20">
                            <h5 class="f-15 f-w-500 mb-0 text-darkest-grey">
                                @lang('app.wo_details')
                            </h5>
                            <a href="{{ route('vendor-credits.show-abstract') }}" class="btn btn-xs btn-primary">
                                Create Bill
                            </a>
                        </div>
                        
                        {{-- Stats Row: Numbers on left, Icon on right --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('work_order.work-order') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalWO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.totalwork_order')</span>
                                </p>
                            </a>
                            <a href="{{ route('work_order.work-order') }}">
                                <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($totalWOAmount)) }}
                                    <span class="f-12 font-weight-normal text-lightest">Total WO Amount</span>
                                </p>
                            </a>
                            
                            <div class="d-block">
                                <i class="fa fa-coins text-lightest f-18"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col mb-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            @lang('app.bill_approvel_dteails')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('bills.index') . '?status=open' }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $tatCount }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.intat')</span>
                                </p>
                            </a>
                            <a href="{{ route('bills.bill-approval') . '?status=pending' }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $overShotCount }}
                                    <span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.overshot') </span>
                                </p>
                            </a>
                        </div>
                    </div>

                    <div class="d-block">
                        <i class="fa fa-coins text-lightest f-18"></i>
                    </div>
                </div>
            </div>
            
            <div class="col mb-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            Total Payment
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('bills.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($totalPaymentAmount)) }}
                                    <span class="f-12 font-weight-normal text-lightest">Total amount</span>
                                </p>
                            </a>
                            <a href="{{ route('bills.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($pendingPaymentAmount)) }}
                                    <span class="f-12 font-weight-normal text-lightest">Pending amount</span>
                                </p>
                            </a>
                        </div>
                    </div>

                    <div class="d-block">
                        <i class="fa fa-coins text-lightest f-18"></i>
                    </div>
                </div>
            </div>

        </div>

        

        <div class="row mt-3">

            {{-- Abstract Bills --}}
            @include('dashboard/common/abstractBillTable')
            {{-- Pending Bills --}}
            @include('dashboard/common/pendingBillTable')
            {{-- Paid Bills --}}
            @include('dashboard/common/paidBillTable')
            {{-- Due Bills --}}
            @include('dashboard/common/dueBillTable')
            
        </div>

        <div class="row mt-3">
            
        </div>
    </div>
@endsection
@include('dashboard/common/js')