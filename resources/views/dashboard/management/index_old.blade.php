@extends('layouts.app')

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
            min-height: 170px;
        }

        .h-200 {
            max-height: 340px;
            overflow-y: auto;
        }

        .dashboard-settings {
            width: 600px;
        }

        @media (max-width: 768px) {
            .dashboard-settings {
                width: 300px;
            }
        }

        .fc-list-event-graphic {
            display: none;
        }

        .fc .fc-list-event:hover td {
            background-color: #fff !important;
            color: #000 !important;
        }

        .left-3 {
            margin-right: -22px;
        }

        .clockin-right {
            margin-right: -10px;
        }

        .week-pagination li {
            margin-right: 5px;
            z-index: 1;
        }

        .week-pagination li a {
            border-radius: 50%;
            padding: 2px 6px !important;
            font-size: 11px !important;
        }

        .week-pagination li.page-item:first-child .page-link {
            border-top-left-radius: 50%;
            border-bottom-left-radius: 50%;
        }

        .week-pagination li.page-item:last-child .page-link {
            border-top-right-radius: 50%;
            border-bottom-right-radius: 50%;
        }

        .mb-3,
        .my-3 {
            margin-bottom: 7px !important;
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
                @php
                    $currentDateTime = now()->timezone(company()->timezone);
                    $msg = null;
                @endphp

                @if (in_array('attendance', user_modules()))
                    @if ((is_null($currentClockIn) && $checkJoiningDate == true) || (is_null($currentClockIn) && $flagbtn == false))
                        <button type="button" class="btn-primary rounded f-15 ml-4" id="clock-in"
                            @if (($cannotLogin || !is_null($msg)) && ($user->isAdmin($user->id) || $user->isEmployee($user->id))) disabled
                                data-toggle="tooltip" data-placement="top" title="{{ __($msg) }}" @endif><i
                                class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_in')</button>
                    @endif
                @endif

                @if (
                    (!is_null($currentClockIn) && is_null($currentClockIn->clock_out_time)) ||
                        (!is_null($currentClockIn) && $flagbtn == true))
                    <button type="button" class="btn-danger rounded f-15 ml-4" id="clock-out"><i
                            class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_out')</button>
                @endif

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
                        <div class="col-xl-12 col-lg-12 col-md-12">
                            <div
                                class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                                <div class="d-block text-capitalize">
                                    <h4 class="f-18 f-w-500 mb-20">@lang('app.menu.invoices')</h4>
                                    <div class="d-flex">
                                        <a href="javascript:;" id="totalPaidInvoices">
                                            <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                                <i id="totalPaidInvoicesCount"></i>22
                                                <span class="f-12 font-weight-normal text-lightest">
                                                    @lang('modules.dashboard.totalPaidInvoices') </span>
                                            </p>
                                        </a>
                                        <a href="javascript:;" id="totalPendingInvoices">
                                            <p class="mb-0 f-21 font-weight-bold text-red d-grid"> <i
                                                    id="totalUnpaidInvoicesCount"></i> 10<span
                                                    class="f-12 font-weight-normal text-lightest">@lang('modules.dashboard.totalUnpaidInvoices')</span>
                                            </p>
                                        </a>
                                        <a href="javascript:;" id="unApproveInvoice">
                                            <p class="mb-0 f-21 font-weight-bold text-blue d-grid ml-5">
                                                <i id="uniqueUnapprovedInvoices"></i>5<span
                                                    class="f-12 font-weight-normal text-lightest">
                                                    @lang('modules.dashboard.unApprove') </span>
                                            </p>
                                        </a>
                                    </div>
                                </div>
                                <div class="d-block"><i class="fa fa-list text-lightest f-27"></i>
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
                                <svg class="svg-inline--fa fa-list fa-w-16 text-lightest f-27" aria-hidden="true"
                                    focusable="false" data-prefix="fa" data-icon="list" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M80 368H16a16 16 0 0 0-16 16v64a16 16 0 0 0 16 16h64a16 16 0 0 0 16-16v-64a16 16 0 0 0-16-16zm0-320H16A16 16 0 0 0 0 64v64a16 16 0 0 0 16 16h64a16 16 0 0 0 16-16V64a16 16 0 0 0-16-16zm0 160H16a16 16 0 0 0-16 16v64a16 16 0 0 0 16 16h64a16 16 0 0 0 16-16v-64a16 16 0 0 0-16-16zm416 176H176a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h320a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0-320H176a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h320a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16zm0 160H176a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h320a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z">
                                    </path>
                                </svg><!-- <i class="fa fa-list text-lightest f-27"></i> Font Awesome fontawesome.com -->
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> Total Bills </h5>
                                <div class="d-flex">
                                    <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=in progress">
                                        <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                            57<span class="f-12 font-weight-normal text-lightest">Submitted</span>
                                        </p>
                                    </a>

                                    <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=overdue">
                                        <p class="mb-0 f-21 font-weight-bold text-red d-grid">
                                            11<span class="f-12 font-weight-normal text-lightest">Approved</span>
                                        </p>
                                    </a>
                                </div>
                            </div>
                            <div class="d-block">
                                <svg class="svg-inline--fa fa-layer-group fa-w-16 text-lightest f-27" aria-hidden="true"
                                    focusable="false" data-prefix="fa" data-icon="layer-group" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M12.41 148.02l232.94 105.67c6.8 3.09 14.49 3.09 21.29 0l232.94-105.67c16.55-7.51 16.55-32.52 0-40.03L266.65 2.31a25.607 25.607 0 0 0-21.29 0L12.41 107.98c-16.55 7.51-16.55 32.53 0 40.04zm487.18 88.28l-58.09-26.33-161.64 73.27c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.51 209.97l-58.1 26.33c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 276.3c16.55-7.5 16.55-32.5 0-40zm0 127.8l-57.87-26.23-161.86 73.37c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.29 337.87 12.41 364.1c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 404.1c16.55-7.5 16.55-32.5 0-40z">
                                    </path>
                                </svg><!-- <i class="fa fa-layer-group text-lightest f-27"></i> Font Awesome fontawesome.com -->
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
                                <svg class="svg-inline--fa fa-layer-group fa-w-16 text-lightest f-27" aria-hidden="true"
                                    focusable="false" data-prefix="fa" data-icon="layer-group" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M12.41 148.02l232.94 105.67c6.8 3.09 14.49 3.09 21.29 0l232.94-105.67c16.55-7.51 16.55-32.52 0-40.03L266.65 2.31a25.607 25.607 0 0 0-21.29 0L12.41 107.98c-16.55 7.51-16.55 32.53 0 40.04zm487.18 88.28l-58.09-26.33-161.64 73.27c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.51 209.97l-58.1 26.33c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 276.3c16.55-7.5 16.55-32.5 0-40zm0 127.8l-57.87-26.23-161.86 73.37c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.29 337.87 12.41 364.1c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 404.1c16.55-7.5 16.55-32.5 0-40z">
                                    </path>
                                </svg><!-- <i class="fa fa-layer-group text-lightest f-27"></i> Font Awesome fontawesome.com -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mt-3 mt-lg-0 mt-md-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">  Bills  Paid Within TAT</h5>
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
                                <svg class="svg-inline--fa fa-layer-group fa-w-16 text-lightest f-27" aria-hidden="true"
                                    focusable="false" data-prefix="fa" data-icon="layer-group" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M12.41 148.02l232.94 105.67c6.8 3.09 14.49 3.09 21.29 0l232.94-105.67c16.55-7.51 16.55-32.52 0-40.03L266.65 2.31a25.607 25.607 0 0 0-21.29 0L12.41 107.98c-16.55 7.51-16.55 32.53 0 40.04zm487.18 88.28l-58.09-26.33-161.64 73.27c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.51 209.97l-58.1 26.33c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 276.3c16.55-7.5 16.55-32.5 0-40zm0 127.8l-57.87-26.23-161.86 73.37c-7.56 3.43-15.59 5.17-23.86 5.17s-16.29-1.74-23.86-5.17L70.29 337.87 12.41 364.1c-16.55 7.5-16.55 32.5 0 40l232.94 105.59c6.8 3.08 14.49 3.08 21.29 0L499.59 404.1c16.55-7.5 16.55-32.5 0-40z">
                                    </path>
                                </svg><!-- <i class="fa fa-layer-group text-lightest f-27"></i> Font Awesome fontawesome.com -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-xl-6 col-lg-12 col-md-12 mt-2">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <x-cards.data :title="__('modules.dashboard.invoiceOverview') .
                                ' <i class=\'fa fa-question-circle\' data-toggle=\'popover\' data-placement=\'top\' data-content=\'' .
                                __('messages.invoicesWidgetMessage') .
                                '\' data-trigger=\'hover\'></i>'">
                                {{-- @if (array_sum($invoiceOverviewChart['values']) > 0)
                                <a href="javascript:;" class="text-darkest-grey f-w-500 piechart-full-screen"
                                    data-chart-id="task-chart1" data-chart-data="{{ json_encode($invoiceOverviewChart) }}"><i
                                        class="fas fa-expand float-right mr-3"></i></a>
                            @endif
                            <x-pie-chart id="task-chart1" :labels="$invoiceOverviewChart['labels']" :values="$invoiceOverviewChart['values']" :colors="$invoiceOverviewChart['colors']" height="350"
                                width="300" /> --}}
                            </x-cards.data>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <x-cards.data :title="__('modules.tasks.paid')" padding="false" otherClasses="h-200">
                                <x-table>
                                    <x-slot name="thead">
                                        <th>@lang('app.invoiceNo')#</th>
                                        <th>@lang('app.contractNo')#</th>
                                        <th class="text-center">@lang('app.contractorName')</th>
                                        <th class="text-right pr-20">@lang('app.dueDate')</th>
                                    </x-slot>


                                    <tr>
                                        <td colspan="7" class="shadow-none">
                                            <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
                                        </td>
                                    </tr>

                                </x-table>
                            </x-cards.data>
                        </div>

                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <x-cards.data :title="__('modules.tasks.dueInvoice')" padding="false" otherClasses="h-200">
                                <x-table>
                                    <x-slot name="thead">
                                        <th>@lang('app.invoiceNo')#</th>
                                        <th>@lang('app.contractNo')#</th>
                                        <th class="text-center">@lang('app.contractorName')</th>
                                        <th class="text-right pr-20">@lang('app.dueDate')</th>
                                    </x-slot>


                                    <tr>
                                        <td colspan="7" class="shadow-none">
                                            <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
                                        </td>
                                    </tr>

                                </x-table>
                            </x-cards.data>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <x-cards.data :title="__('modules.tasks.unApprove')" padding="false" otherClasses="h-200">
                                <x-table>
                                    <x-slot name="thead">
                                        <th>@lang('app.invoiceNo')#</th>
                                        <th>@lang('app.contractNo')#</th>
                                        <th class="text-center">@lang('app.contractorName')</th>
                                        <th class="text-right pr-20">@lang('app.dueDate')</th>
                                    </x-slot>


                                    <tr>
                                        <td colspan="7" class="shadow-none">
                                            <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
                                        </td>
                                    </tr>

                                </x-table>
                            </x-cards.data>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

<script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
<script src="{{ asset('vendor/jquery/Chart.min.js') }}"></script>

@push('scripts')
    <script>
        window.setInterval(function() {
            let date = new Date();
            $('#dashboard-clock').html(moment.tz(date, "{{ company()->timezone }}").format(MOMENTJS_TIME_FORMAT))
        }, 1000);
        $('#clock-in').click(function() {
            const url = "{{ route('attendances.clock_in_modal') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });



        $('.keep-open .dropdown-menu').on({
            "click": function(e) {
                e.stopPropagation();
            }
        });


        @if (session('success'))
            Swal.fire({
                icon: 'success',
                text: '{{ session('success') }}',

                toast: true,
                position: "top-end",
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,

                customClass: {
                    confirmButton: "btn btn-primary",
                },
                showClass: {
                    popup: "swal2-noanimation",
                    backdrop: "swal2-noanimation",
                },
            });
        @endif
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                text: '{{ session('error') }}',

                toast: true,
                position: "top-end",
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,

                customClass: {
                    confirmButton: "btn btn-primary",
                },
                showClass: {
                    popup: "swal2-noanimation",
                    backdrop: "swal2-noanimation",
                },
            });
        @endif


        function fetchInvoiceCount() {
            $.ajax({
                url: '',
                type: "GET",
                dataType: "json",
                success: function(response) {
                    console.log("Response:", response); // Debugging

                    $("#totalInvoicesCount").text(response.totalInvoices ?? 0);
                    $("#totalPaidInvoicesCount").text(response.totalPaidInvoices ?? 0);
                    $("#totalUnpaidInvoicesCount").text(response.totalUnpaidInvoices ?? 0);
                    $("#totalUnapproved").text(response.totalUnapproved ?? 0);
                    $("#totalApproved").text(response.totalApproved ?? 0);
                    $("#totalApprovalItems").text(response.totalApprovalItems ?? 0);
                    $("#uniqueUnapprovedInvoices").text(response.uniqueUnapprovedInvoices ?? 0);


                },
                error: function(xhr) {
                    console.error("Error fetching invoice count:", xhr.responseText);
                }
            });
        }

        $(document).ready(function() {
            fetchInvoiceCount();
            setInterval(fetchInvoiceCount, 60000); // Refresh every 60 seconds
        });

        $('#totalPaidInvoices').click(function() {
            // alert();
            // var dateRange = getDateRange();
            var url = `{{ route('invoices.index') . '?status=paid' }}`;

            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });

        $('#totalPendingInvoices').click(function() {
            // var dateRange = getDateRange();
            var url = `{{ route('invoices.index') . '?status=pending' }}`;

            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });
        $('#totalInvoices').click(function() {
            // var dateRange = getDateRange();
            var url = `{{ route('invoices.index') }}`;

            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });
        $('#unApproveInvoice').click(function() {
            // var dateRange = getDateRange();
            var url = `{{ route('invoices.index') . '?statusa=unapprove' }}`;

            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });

        $('#totalApprovalItems').click(function() {
            // var dateRange = getDateRange();
            var url = ``;
            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });

        $('#totalApproved').click(function() {
            // var dateRange = getDateRange();
            var url = ``;
            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });
        $('#totalUnapproved').click(function() {
            // var dateRange = getDateRange();
            var url = ``;
            // string = `&start=${dateRange.startDate}&end=${dateRange.endDate}`;
            // url += string;

            window.location.href = url;
        });

        $(document).on('click', '.redirectInvoice', function() {
            var invoiceId = $(this).data('invoice-id'); // Get invoice ID from data attribute
            var url = `{{ route('invoices.show', ['invoice' => ':invoiceId']) }}`.replace(':invoiceId', invoiceId);
            window.location.href = url;
        });
    </script>
@endpush
