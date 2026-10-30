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


@endpush
<style>
    .card-body.p-0.h-200 {
        min-height: 287px;
        padding: 10px !important;
    }

    .dt-layout-cell.dt-layout-start {
        display: none !important;
    }
</style>
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
                @if ($instantDebitPermission == 'all')
                    <button type="button" class="btn btn-info rounded f-15 ml-4 btnAdd" id="instantDebit"><i
                            class="icons icon-plus mr-2"></i>{{ __('app.addinstantDebitAmount') }}</button>
                @endif
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
                                                    {{ $user->employeeDetail->designation->name ?? '--' }}</p>
                                                <p class="card-text f-12 text-lightest"> @lang('app.employeeId') :
                                                    {{ $user->employeeDetail->employee_id }}</p>
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
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            @lang('app.bill_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('bills.index') . '?status=paid' }}">
                                <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                    {{ $totalBill }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.total_bills')</span>
                                </p>
                            </a>
                            <a href="{{ route('bills.index') . '?status=paid' }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $paidCount }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.paid_bills')</span>
                                </p>
                            </a>
                            <a href="{{ route('bills.index') . '?status=due' }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $dueCount }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.due_bills') </span>
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
                            @lang('app.bill_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('projects.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $abstractCount }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.abstract')</span>
                                </p>
                            </a>
                            <a href="{{ route('tasks.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $pendingCount }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.pending') </span>
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
            {{-- -- Total vs Paid Bills --}}
            @include('dashboard/common/chart/totalVSpaidBill')
        </div>
    </div>
@endsection
@include('dashboard/common/js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);

        renderBarChart(
            'bar',
            'totalVSpaidBill',
            @json($chartLabels),
            [{
                    label: 'Due + Paid (Total)',
                    data: @json($chartTotalCombined),
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'Total Paid',
                    data: @json($chartPaidAmounts),
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                },
                {
                    label: 'Total Due',
                    data: @json($chartTotalAmounts),
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }
            ],
            'Amount in Crores (₹)',
            false

        );


    });
</script>
