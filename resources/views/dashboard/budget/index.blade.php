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

                {{-- @if (in_array('attendance', user_modules()))
                    @if ((is_null($currentClockIn) && $checkJoiningDate == true) || (is_null($currentClockIn) && $flagbtn == false))
                        <button type="button" class="btn-primary rounded f-15 ml-4" id="clock-in"
                            @if (($cannotLogin || !is_null($msg)) && ($user->isAdmin($user->id) || $user->isEmployee($user->id))) disabled
                                data-toggle="tooltip" data-placement="top" title="{{ __($msg) }}" @endif><i
                                class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_in')</button>
                    @endif
                @endif --}}

                {{-- @if ((!is_null($currentClockIn) && is_null($currentClockIn->clock_out_time)) || (!is_null($currentClockIn) && $flagbtn == true))
                    <button type="button" class="btn-danger rounded f-15 ml-4" id="clock-out"><i
                            class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_out')</button>
                @endif --}}

                @if (in_array('admin', user_roles()))
                    <div class="private-dash-settings d-flex align-self-center">
                        <x-form id="privateDashboardWidgetForm" method="POST">
                            <div class="dropdown keep-open">
                                <a class="d-flex align-items-center justify-content-center dropdown-toggle px-4 text-dark left-3"
                                    type="link" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    <i class="fa fa-cog"
                                        data-original-title="{{ __('modules.dashboard.dashboardWidgetsSettings') }}"
                                        data-toggle="tooltip"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right dashboard-settings p-20"
                                    aria-labelledby="dropdownMenuLink" tabindex="0">
                                    <li class="border-bottom mb-3">
                                        <h4 class="heading-h3">@lang('modules.dashboard.dashboardWidgets')</h4>
                                    </li>
                                    @foreach ($widgets as $widget)
                                        @php
                                            $wname = \Illuminate\Support\Str::camel($widget->widget_name);
                                        @endphp
                                        <li class="mb-2 float-left w-50">
                                            <div class="checkbox checkbox-info ">
                                                <input id="{{ $widget->widget_name }}" name="{{ $widget->widget_name }}"
                                                    value="true" @if ($widget->status) checked @endif
                                                    type="checkbox">
                                                <label for="{{ $widget->widget_name }}">@lang('modules.dashboard.' . $wname)</label>
                                            </div>
                                        </li>
                                    @endforeach
                                    @if (count($widgets) % 2 != 0)
                                        <li class="mb-2 float-left w-50 height-35"></li>
                                    @endif
                                    <li class="float-none w-100">
                                        <x-forms.button-primary id="save-dashboard-widget" icon="check">@lang('app.save')
                                        </x-forms.button-primary>
                                    </li>
                                </ul>
                            </div>
                        </x-form>
                    </div>
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

                                    {{-- <div class="card-footer bg-white border-top-grey py-3">
                                    <div class="d-flex flex-wrap justify-content-between">
                                        @if (in_array('tasks', user_modules()))
                                            <span>
                                                <label class="f-12 text-dark-grey mb-12 " for="usr">
                                                    @lang('app.openTasks') </label>
                                                <p class="mb-0 f-18 f-w-500">
                                                    <a href="{{ route('tasks.index') . '?assignee=me' }}"
                                                        class="text-dark">
                                                        {{ $inProcessTasks }}
                                                    </a>
                                                </p>
                                            </span>
                                        @endif
                                        @if (in_array('projects', user_modules()))
                                            <span>
                                                <label class="f-12 text-dark-grey mb-12 " for="usr">
                                                    @lang('app.menu.projects') </label>
                                                <p class="mb-0 f-18 f-w-500">
                                                    <a href="{{ route('projects.index') . '?assignee=me&status=all' }}"
                                                        class="text-dark">{{ $totalProjects }}</a>
                                                </p>
                                            </span>
                                        @endif
                                        @if (isset($totalOpenTickets) && in_array('tickets', user_modules()))
                                            <span>
                                                <label class="f-12 text-dark-grey mb-12 " for="usr">
                                                    @lang('modules.dashboard.totalOpenTickets') </label>
                                                <p class="mb-0 f-18 f-w-500">
                                                    <a href="{{ route('tickets.index') . '?agent=me&status=open' }}"
                                                        class="text-dark">{{ $totalOpenTickets }}</a>
                                                </p>
                                            </span>
                                        @endif
                                    </div>
                                </div> --}}
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
                            @lang('app.project_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('projects.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalProjects }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.projects')</span>
                                </p>
                            </a>
                            <a href="{{ route('tasks.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $totalSubProjects }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.subProjects') </span>
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
                            @lang('app.po_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('purchase-order.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalPO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.purchase_order')</span>
                                </p>
                            </a>

                            <a href="{{ route('bills.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                    {{ $totalBill }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.bills') </span>
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
            {{-- -- Budget vs Actual vs PO --}}
            @include('dashboard/common/chart/budgetvsactualvspo')

            {{-- Total Budget Cost --}}
            <div class="col-md-4 mt-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">Total Budget</h5>
                        <div class="d-flex">
                            <p class="mb-0 f-15 font-weight-bold text-blue text-primary d-grid">
                                <span>
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($budgetCostSummary->total_budget_cost_all ?? 0)) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-wallet text-lightest f-18"></i>
                    </div>
                </div>
            </div>

            {{-- Current Month Budget --}}
            <div class="col-md-4 mt-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">This Month Budget</h5>
                        <div class="d-flex">
                            <p class="mb-0 f-15 font-weight-bold text-success d-grid">
                                <span>
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($budgetCostSummary->total_budget_cost_current_month ?? 0)) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-calendar-alt text-lightest f-18"></i>
                    </div>
                </div>
            </div>

            {{-- Financial Year Budget --}}
            <div class="col-md-4 mt-3">
                <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">Financial Year Budget</h5>
                        <div class="d-flex">
                            <p class="mb-0 f-15 font-weight-bold text-warning d-grid">
                                <span>
                                    ₹{{ \App\Helper\NumberFormat::formatIndianCurrency(round($budgetCostSummary->total_budget_cost_financial_year ?? 0)) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-chart-line text-lightest f-18"></i>
                    </div>
                </div>
            </div>

            @include('dashboard/common/chart/budgetVsCommitmentVsActualAllBuildings')
            @include('dashboard/common/chart/budgetChartData')




            @include('dashboard/common/chart/budgetvsactualvspoDev')
            @include('dashboard/common/chart/budgetvsactualvspoTech')

        </div>

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection
@include('dashboard/common/js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);
        renderBarChart(
            'bar',
            'budgetvsactualvspo',
            @json($totalWBSGraph['labels']),
            [{
                    label: 'Total Amount (Cr)',
                    data: @json($totalWBSGraph['values'][0]),
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($totalWBSGraph['values'][1]),
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($totalWBSGraph['values'][2]),
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                }
            ],
            'Amount in Crores (₹)',
            true

        );

        renderBarChart(
            'bar',
            'budgetvsactualvspoBuildiing',
            @json($graphBuild['labels']),
            [{
                    label: 'Total Amount (Cr)',
                    data: @json($graphBuild['values'][0]),
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($graphBuild['values'][1]),
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($graphBuild['values'][2]),
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                }
            ],
            'Amount in Crores (₹)',
            true

        );

        renderBarChart(
            'bar',
            'budgetvsactualvspoDev',
            @json($devItemsChart['labels']),
            [{
                    label: 'Total Amount (Cr)',
                    data: @json($devItemsChart['values'][0]),
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($devItemsChart['values'][1]),
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($devItemsChart['values'][2]),
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                }
            ],
            'Amount in Crores (₹)',
            true

        );

        renderBarChart(
            'bar',
            'budgetvsactualvspoTech',
            @json($techItemsChart['labels']),
            [{
                    label: 'Total Amount (Cr)',
                    data: @json($techItemsChart['values'][0]),
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($techItemsChart['values'][1]),
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Validated Amount (Cr)',
                    data: @json($techItemsChart['values'][2]),
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                }
            ],
            'Amount in Crores (₹)',
            true

        );

renderBarChart(
    'bar',
    'budgetChartData',
    @json($budgetChartData['labels']),
    [{
        label: 'Amount (Cr)', // Single string describing the dataset
        data: @json(array_map(fn($v) => round($v / 10000000, 2), $budgetChartData['totals'])),
        backgroundColor: ['#007bff', '#28a745', '#ffc107'], // Blue, Green, Yellow for Build, Dev, Tech
        borderColor: ['#007bff', '#28a745', '#ffc107'], // Matching borders for each color
        borderWidth: 1
    }],
    'Amount in Crores (₹)',
    false
);




    });
</script>
