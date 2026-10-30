@extends('layouts.app')

@push('styles')

    @if (
        (!is_null($viewEventPermission) && $viewEventPermission != 'none') ||
            (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none') ||
            (!is_null($viewTaskPermission) && $viewTaskPermission != 'none') ||
            (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none') ||
            (!is_null($viewLeavePermission) && $viewLeavePermission != 'none'))
    @endif

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
                @php
                    $currentDateTime = now()->timezone(company()->timezone);
                    $msg = null;
                @endphp
            </div>
        </div>
        <br>
        <div class="row emp-dash-detail">
            @if (count(array_intersect(['profile', 'shift_schedule', 'birthday', 'notices'], $activeWidgets)) > 0)
                <div class="col-xl-6 col-lg-12 col-md-12 e-d-info-notices">
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
                        <div class="col-xl-7 col-lg-12 col-md-12">
                            <div
                                class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                                <div class="d-block text-capitalize">
                                    <h4 class="f-18 f-w-500 mb-20">@lang('app.billstatus')</h4>
                                    <div class="d-flex">
                                        <a href="{{ url('account/bills') }}" id="totalPaidInvoices">
                                            <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                                <i id="totalPaidInvoicesCount"></i>{{ $purchase_bills_count['open'] }}
                                                <span class="f-12 font-weight-normal text-lightest">
                                                    @lang('app.open') </span>
                                            </p>
                                        </a>
                                        <a href={{ url('account/bills') }}" id="totalPendingInvoices">
                                            <p class="mb-0 f-21 font-weight-bold text-red d-grid"> <i
                                                    id="totalUnpaidInvoicesCount"></i> {{ $purchase_bills_count['draft'] }}
                                                <span class<span
                                                    class="f-12 font-weight-normal text-lightest">@lang('app.draft')</span>
                                            </p>
                                        </a>
                                        <a href="{{ url('account/bills') }}" id="unApproveInvoice">
                                            <p class="mb-0 f-21 font-weight-bold text-blue d-grid ml-5">
                                                <i
                                                    id="uniqueUnapprovedInvoices"></i>{{ $purchase_bills_count['abstract'] }}<span
                                                    class="f-12 font-weight-normal text-lightest">
                                                    @lang('app.abstract') </span>
                                            </p>
                                        </a>
                                    </div>
                                </div>
                                <div class="d-block"><i class="fa fa-list text-lightest f-27"></i>
                                </div>
                            </div>


                        </div>

                        <div class="col-md-5">
                            <div
                                class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                                <div class="d-block text-capitalize">
                                    <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">Total Project </h5>
                                    <div class="d-flex">
                                        <a
                                            href="{{ url('account/projects')}}">
                                            <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                                {{ $total_projects }}<span
                                                    class="f-12 font-weight-normal text-lightest">Projects</span>
                                            </p>
                                        </a>


                                    </div>
                                </div>
                                <div class="d-block">
                                    <i class="fa fa-layer-group text-lightest f-27"></i>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @endif
            <div class="col-xl-6 col-lg-12 col-md-12 e-d-tasks-projects-events">
                <div class="row mb-3 mt-xl-0 mt-lg-4 mt-md-4 mt-4">
                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">Approval Bills</h5>
                                <div class="d-flex">
                                    <a href="http://portal.vertscend.com/public/account/tasks?assignee=me">
                                        <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                            12<span class="f-12 font-weight-normal text-lightest">
                                                Delayed </span>
                                        </p>
                                    </a>
                                    <a href="http://portal.vertscend.com/public/account/tasks?assignee=me&amp;overdue=yes">
                                        <p class="mb-0 f-21 font-weight-bold text-red d-grid">23<span
                                                class="f-12 font-weight-normal text-lightest">Pending</span>
                                        </p>
                                    </a>
                                </div>
                            </div>
                            <div class="d-block">
                                <i class="fa fa-list text-lightest f-27"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> Total Bills </h5>
                                <div class="d-flex">
                                    <a href="{{ url('account/bills') }}">
                                        <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                            {{ $total_bills }}<span
                                                class="f-12 font-weight-normal text-lightest">Submitted</span>
                                        </p>
                                    </a>

                                    {{-- <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=overdue">
                                        <p class="mb-0 f-21 font-weight-bold text-red d-grid">
                                            11<span class="f-12 font-weight-normal text-lightest">Approved</span>
                                        </p>
                                    </a> --}}
                                </div>
                            </div>
                            <div class="d-block">
                                <i class="fa fa-layer-group text-lightest f-27"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> Dalayed Payment Bills </h5>
                                <div class="d-flex">
                                    <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=in progress">
                                        <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                            8<span class="f-12 font-weight-normal text-lightest">Bills</span>
                                        </p>
                                    </a>


                                </div>
                            </div>
                            <div class="d-block">
                                <i class="fa fa-layer-group text-lightest f-27"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> Bills Paid Within TAT</h5>
                                <div class="d-flex">


                                    <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=overdue">
                                        <p class="mb-0 f-21 font-weight-bold text-red d-grid">
                                            42<span class="f-12 font-weight-normal text-lightest">Bills</span>
                                        </p>
                                    </a>
                                </div>
                            </div>
                            <div class="d-block">
                                <i class="fa fa-layer-group text-lightest f-27"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="row">
            
            <div class="col-sm-12 col-lg-12 mt-3">
                <x-cards.data :title="__('app.stagesVsBoq') .
                    ' <i class=\'fa fa-question-circle\' data-toggle=\'popover\' data-placement=\'top\' data-content=\'' .
                    __('app.lastTweleveMonths') .
                    '\' data-trigger=\'hover\'></i>'">

                    <x-line-chart id="regular-delayed-chart" :chartData="$stagesVsBoqChart1Data1" height="250" multiple="true"
                        chartType="line" />
                </x-cards.data>
            </div>
            <div class="col-sm-12 col-lg-12 mt-3">
                <x-cards.data :title="__('app.abstractvsdreaftveopen') .
                    ' <i class=\'fa fa-question-circle\' data-toggle=\'popover\' data-placement=\'top\' data-content=\'' .
                    __('app.lastTweleveMonths') .
                    '\' data-trigger=\'hover\'></i>'">

                    <x-line-chart id="bill-chart" :chartData="$billChart1Data1" height="250" multiple="true" chartType="bar" />
                </x-cards.data>
            </div>
            <div class="col-sm-12 col-lg-12 mt-3">
                <x-cards.data :title="__('modules.dashboard.paidVsUnpaid') .
                    ' <i class=\'fa fa-question-circle\' data-toggle=\'popover\' data-placement=\'top\' data-content=\'' .
                    __('app.lastTweleveMonths') .
                    '\' data-trigger=\'hover\'></i>'">

                    <x-line-chart id="regular-delayed-chart1" :chartData="$paidVsUnpaidChartData1" height="250" multiple="true"
                        chartType="line" />
                </x-cards.data>
            </div>
        </div>
    </div>


@endsection

<script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
<script src="{{ asset('vendor/jquery/Chart.min.js') }}"></script>

@push('scripts')
@endpush
