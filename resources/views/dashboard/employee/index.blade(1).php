@extends('layouts.app')

@push('styles')
    @if (
        (!is_null($viewEventPermission) && $viewEventPermission != 'none') ||
            (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none') ||
            (!is_null($viewTaskPermission) && $viewTaskPermission != 'none') ||
            (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none') ||
            (!is_null($viewLeavePermission) && $viewLeavePermission != 'none'))
        <link rel="stylesheet" href="{{ asset('vendor/full-calendar/main.min.css') }}" defer="defer">
    @endif
    <style>
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
    </style>
@endpush

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="px-4 py-2 border-top-0 emp-dashboard">
        <!-- WELOCOME START -->
        @if (!is_null($checkTodayLeave))
            <div class="row pt-4">
                <div class="col-md-12">
                    <x-alert type="info" icon="info-circle">
                        <a href="{{ route('leaves.show', $checkTodayLeave->id) }}" class="openRightModal text-dark-grey">
                            <u>@lang('messages.youAreOnLeave')</u>
                        </a>
                    </x-alert>
                </div>
            </div>
        @elseif (!is_null($checkTodayHoliday))
            <div class="row pt-4">
                <div class="col-md-12">
                    <x-alert type="info" icon="info-circle">
                        <a href="{{ route('holidays.show', $checkTodayHoliday->id) }}"
                            class="openRightModal text-dark-grey">
                            <u>@lang('messages.holidayToday')</u>
                        </a>
                    </x-alert>
                </div>
            </div>
        @endif


        @if (in_array('admin', user_roles()))
            <div class="row">
                @include('dashboard.update-message-dashboard')
                @includeIf('dashboard.update-message-module-dashboard')
                <x-cron-message :modal="true"></x-cron-message>
            </div>
        @endif

        <div class="d-lg-flex d-md-flex d-block py-2 pb-2 align-items-center">

            <!-- WELOCOME NAME START -->
            <div>
                <h3 class="heading-h3 mb-0 f-21 font-weight-bold">@lang('app.welcome') {{ $user->name }}</h3>
            </div>
            <!-- WELOCOME NAME END -->

            <!-- CLOCK IN CLOCK OUT START -->
            <div class="ml-auto d-flex clock-in-out mb-3 mb-lg-0 mb-md-0 m mt-4 mt-lg-0 mt-md-0 justify-content-between">
                <p class="mb-0 text-lg-right text-md-right f-18 font-weight-bold text-dark-grey d-grid align-items-center">
                    <input type="hidden" id="current-latitude" name="current_latitude">
                    <input type="hidden" id="current-longitude" name="current_longitude">

                    <span id="dashboard-clock">{!! now()->timezone(company()->timezone)->translatedFormat(company()->time_format) .
                        '</span><span class="f-10 font-weight-normal">' .
                        now()->timezone(company()->timezone)->translatedFormat('l') .
                        '</span>' !!}

                        @if (!is_null($currentClockIn))
                            <span class="f-11 font-weight-normal text-lightest">
                                @lang('app.clockInAt') -
                                {{ $currentClockIn->clock_in_time->timezone(company()->timezone)->translatedFormat(company()->time_format) }}
                            </span>
                        @endif
                </p>

                @php
                    $current_time = now()->timezone(company()->timezone)->translatedFormat(company()->time_format);
                    $msg = null;
                    $strt = \Carbon\Carbon::createFromFormat(
                        'H:i:s',
                        $attendanceSettings->office_start_time,
                    )->translatedFormat(company()->time_format);
                    $end_time = \Carbon\Carbon::createFromFormat(
                        'H:i:s',
                        $attendanceSettings->office_end_time,
                    )->translatedFormat(company()->time_format);

                    // if ($end_time < $strt) {
                    //     $end_time += 24 * 3600; // Adding one day worth of seconds (24 hours * 3600 seconds)
                    // }

                    $current_user_id = auth()->user()->id; // Assuming you're using Laravel's authentication and getting the current user ID
                    $leave_for_current_user = $leave->where('user_id', $current_user_id)->first();

                    if ($leave_for_current_user != null) {
                        $msg = 'Leave is applied for today.';
                    } elseif (!is_null($checkTodayHoliday) && $checkTodayHoliday->exists) {
                        $msg = 'Today is Holiday.';
                    } elseif ($cannotLogin == true) {
                        $msg = 'You are not within office hours.';
                    }

                @endphp
                {{-- @if (in_array('attendance', user_modules()))
                    @if (is_null($currentClockIn) && $checkJoiningDate == true)
                        <button type="button" class="btn-primary rounded f-15 ml-4" id="clock-in"
                            @if (($cannotLogin || !is_null($msg)) && ($user->isAdmin($user->id) || $user->isEmployee($user->id))) disabled
                                data-toggle="tooltip" data-placement="top" title="{{ __($msg) }}" @endif><i
                                class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_in')</button>
                    @endif
                @endif

                @if (!is_null($currentClockIn) && is_null($currentClockIn->clock_out_time))
                    <button type="button" class="btn-danger rounded f-15 ml-4" id="clock-out"><i
                            class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_out')</button>
                @endif --}}





 @if($instantDebitPermission == 'all')
                <button type="button" class="btn btn-info rounded f-15 ml-4 btnAdd" id="companyGroup"><i
                        class="icons icon-plus mr-2"></i>{{ __('app.addinstantDebitAmount') }}</button>

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
                                <!-- Dropdown - User Information -->
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
            <!-- CLOCK IN CLOCK OUT END -->
        </div>
        <!-- WELOCOME END -->
        <!-- EMPLOYEE DASHBOARD DETAIL START -->
        <div class="row emp-dash-detail">
            <!-- EMP DASHBOARD INFO NOTICES START -->
            @if (count(array_intersect(['profile', 'shift_schedule', 'birthday', 'notices'], $activeWidgets)) > 0)
                <div class="col-xl-5 col-lg-12 col-md-12 e-d-info-notices">
                    <div class="row">
                        @if (in_array('profile', $activeWidgets))
                            <!-- EMP DASHBOARD INFO START -->
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
                            <!-- EMP DASHBOARD INFO END -->
                        @endif

                    </div>
                </div>
            @endif
            <!-- EMP DASHBOARD INFO NOTICES END -->
            <!-- EMP DASHBOARD TASKS PROJECTS EVENTS START -->
            <div class="col-xl-7 col-lg-12 col-md-12 e-d-tasks-projects-events">
                <!-- EMP DASHBOARD TASKS PROJECTS START -->
                <div class="row mb-3 mt-xl-0 mt-lg-4 mt-md-4 mt-4">
                    <div class="col-md-6 mb-3">
                        <div
                            class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                            <div class="d-block text-capitalize">
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">Bills Under Approval</h5>
                                <div class="d-flex">
                                    <a href="http://portal.vertscend.com/public/account/tasks?assignee=me">
                                        <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                            32<span class="f-12 font-weight-normal text-lightest">
                                                Invoices (8 Delayed) </span>
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
                                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey"> Bills For Payment </h5>
                                <div class="d-flex">


                                    <a
                                        href="http://portal.vertscend.com/public/account/projects?assignee=me&amp;status=overdue">
                                        <p class="mb-0 f-21 font-weight-bold text-red d-grid">
                                            17<span class="f-12 font-weight-normal text-lightest">Invoices (5
                                                Delayed)</span>
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
            <!-- EMP DASHBOARD TASKS PROJECTS EVENTS END -->
        </div>
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 e-d-tasks-projects-events">
                <div class="row">
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <div class="card bg-white border-0 b-shadow-4">
                                <div
                                    class="card-header bg-white border-0 text-capitalize d-flex justify-content-between pt-4">
                                    <h4 class="f-18 f-w-500 mb-0">Paid Invoice</h4>



                                </div>

                                <div class="card-body p-0 h-200">
                                    <table class="table" id="example">
                                        <thead class="">
                                            <tr>
                                                <th>Invoice Number#</th>
                                                <th>PO Number#</th>
                                                <th class="text-center">Vendor Name</th>
                                                <th class="text-right pr-20">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="7" class="shadow-none">
                                                    <div
                                                        class="align-items-center d-flex flex-column text-lightest p-20 w-100">
                                                        <svg class="svg-inline--fa fa-tasks fa-w-16 f-21 w-100"
                                                            aria-hidden="true" focusable="false" data-prefix="fa"
                                                            data-icon="tasks" role="img"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                                            data-fa-i2svg="">
                                                            <path fill="currentColor"
                                                                d="M139.61 35.5a12 12 0 0 0-17 0L58.93 98.81l-22.7-22.12a12 12 0 0 0-17 0L3.53 92.41a12 12 0 0 0 0 17l47.59 47.4a12.78 12.78 0 0 0 17.61 0l15.59-15.62L156.52 69a12.09 12.09 0 0 0 .09-17zm0 159.19a12 12 0 0 0-17 0l-63.68 63.72-22.7-22.1a12 12 0 0 0-17 0L3.53 252a12 12 0 0 0 0 17L51 316.5a12.77 12.77 0 0 0 17.6 0l15.7-15.69 72.2-72.22a12 12 0 0 0 .09-16.9zM64 368c-26.49 0-48.59 21.5-48.59 48S37.53 464 64 464a48 48 0 0 0 0-96zm432 16H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0-320H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16zm0 160H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z">
                                                            </path>
                                                        </svg><!-- <i class="fa fa-tasks f-21 w-100"></i> Font Awesome fontawesome.com -->

                                                        <div class="f-15 mt-4">
                                                            - No record found. -
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <div class="card bg-white border-0 b-shadow-4">
                                <div
                                    class="card-header bg-white border-0 text-capitalize d-flex justify-content-between pt-4">
                                    <h4 class="f-18 f-w-500 mb-0">Due Invoice</h4>



                                </div>

                                <div class="card-body p-0 h-200">
                                    <table class="table" id="example">
                                        <thead class="">
                                            <tr>
                                                <th>Invoice Number#</th>
                                                <th>PO Number#</th>
                                                <th class="text-center">Vendor Name</th>
                                                <th class="text-right pr-20">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="7" class="shadow-none">
                                                    <div
                                                        class="align-items-center d-flex flex-column text-lightest p-20 w-100">
                                                        <svg class="svg-inline--fa fa-tasks fa-w-16 f-21 w-100"
                                                            aria-hidden="true" focusable="false" data-prefix="fa"
                                                            data-icon="tasks" role="img"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                                            data-fa-i2svg="">
                                                            <path fill="currentColor"
                                                                d="M139.61 35.5a12 12 0 0 0-17 0L58.93 98.81l-22.7-22.12a12 12 0 0 0-17 0L3.53 92.41a12 12 0 0 0 0 17l47.59 47.4a12.78 12.78 0 0 0 17.61 0l15.59-15.62L156.52 69a12.09 12.09 0 0 0 .09-17zm0 159.19a12 12 0 0 0-17 0l-63.68 63.72-22.7-22.1a12 12 0 0 0-17 0L3.53 252a12 12 0 0 0 0 17L51 316.5a12.77 12.77 0 0 0 17.6 0l15.7-15.69 72.2-72.22a12 12 0 0 0 .09-16.9zM64 368c-26.49 0-48.59 21.5-48.59 48S37.53 464 64 464a48 48 0 0 0 0-96zm432 16H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0-320H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16zm0 160H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z">
                                                            </path>
                                                        </svg><!-- <i class="fa fa-tasks f-21 w-100"></i> Font Awesome fontawesome.com -->

                                                        <div class="f-15 mt-4">
                                                            - No record found. -
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-12 col-md-12">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <div class="card bg-white border-0 b-shadow-4">
                                <div
                                    class="card-header bg-white border-0 text-capitalize d-flex justify-content-between pt-4">
                                    <h4 class="f-18 f-w-500 mb-0">Un-Approve Invoice</h4>



                                </div>

                                <div class="card-body p-0 h-200">
                                    <table class="table" id="example">
                                        <thead class="">
                                            <tr>
                                                <th>Invoice Number#</th>
                                                <th>PO Number#</th>
                                                <th class="text-center">Vendor Name</th>
                                                <th class="text-right pr-20">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="7" class="shadow-none">
                                                    <div
                                                        class="align-items-center d-flex flex-column text-lightest p-20 w-100">
                                                        <svg class="svg-inline--fa fa-tasks fa-w-16 f-21 w-100"
                                                            aria-hidden="true" focusable="false" data-prefix="fa"
                                                            data-icon="tasks" role="img"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                                                            data-fa-i2svg="">
                                                            <path fill="currentColor"
                                                                d="M139.61 35.5a12 12 0 0 0-17 0L58.93 98.81l-22.7-22.12a12 12 0 0 0-17 0L3.53 92.41a12 12 0 0 0 0 17l47.59 47.4a12.78 12.78 0 0 0 17.61 0l15.59-15.62L156.52 69a12.09 12.09 0 0 0 .09-17zm0 159.19a12 12 0 0 0-17 0l-63.68 63.72-22.7-22.1a12 12 0 0 0-17 0L3.53 252a12 12 0 0 0 0 17L51 316.5a12.77 12.77 0 0 0 17.6 0l15.7-15.69 72.2-72.22a12 12 0 0 0 .09-16.9zM64 368c-26.49 0-48.59 21.5-48.59 48S37.53 464 64 464a48 48 0 0 0 0-96zm432 16H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0-320H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16V80a16 16 0 0 0-16-16zm0 160H208a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h288a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z">
                                                            </path>
                                                        </svg><!-- <i class="fa fa-tasks f-21 w-100"></i> Font Awesome fontawesome.com -->

                                                        <div class="f-15 mt-4">
                                                            - No record found. -
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- EMPLOYEE DASHBOARD DETAIL END -->

    </div>
    <!-- CONTENT WRAPPER END -->

    @include('dashboard.employee.section.addinstantDebitAmount');


@endsection

@push('scripts')
    <script>
        $('#companyGroup').click(function() {
            $('#companyGroupModal').modal('show');
        });
        $('.closeMod').click(function() {
            $('.modal').modal('hide');
        });
    </script>
    @if (
        (!is_null($viewEventPermission) && $viewEventPermission != 'none') ||
            (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none') ||
            (!is_null($viewTaskPermission) && $viewTaskPermission != 'none') ||
            (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none') ||
            (!is_null($viewLeavePermission) && $viewLeavePermission != 'none'))
        <script src="{{ asset('vendor/full-calendar/main.min.js') }}" defer="defer"></script>
        <script src="{{ asset('vendor/full-calendar/locales-all.min.js') }}" defer="defer"></script>
        <script>
            if (cannotLogin === false) {
                clockInButton.disabled = true;
                clockInButton.setAttribute('data-toggle', 'popover');
                clockInButton.setAttribute('data-trigger', 'hover');
                // Add other attributes as needed
            } else {
                clockInButton.disabled = false;
                // Remove any attributes set when the button is disabled
                clockInButton.removeAttribute('data-toggle');
                clockInButton.removeAttribute('data-trigger');
                // Remove other attributes as needed
            }
        </script>
        <script>
            $(document).ready(function() {
                var calendarEl = document.getElementById('calendar');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: initialLocaleCode,
                    timeZone: '{{ company()->timezone }}',
                    firstDay: parseInt("{{ attendance_setting()?->week_start_from }}"),
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    navLinks: true, // can click day/week names to navigate views
                    selectable: false,
                    initialView: 'listWeek',
                    selectMirror: true,
                    select: function(arg) {
                        addEventModal(arg.start, arg.end, arg.allDay);
                        calendar.unselect()
                    },
                    eventClick: function(arg) {
                        getEventDetail(arg.event.id, arg.event.extendedProps.event_type);
                    },
                    editable: false,
                    dayMaxEvents: true, // allow "more" link when too many events
                    events: {
                        url: "{{ route('dashboard.private_calendar') }}",
                    },
                    eventDidMount: function(info) {
                        $(info.el).css('background-color', info.event.extendedProps.bg_color);
                        $(info.el).css('color', info.event.extendedProps.color);
                        $(info.el).find('td.fc-list-event-title').prepend('<i class="fa ' + info.event
                            .extendedProps.icon + '"></i>&nbsp;&nbsp;');
                        // tooltip for leaves
                        if (info.event.extendedProps.event_type == 'leave') {
                            $(info.el).find('td.fc-list-event-title > a').css('cursor',
                                'default'); // list view cursor for leave
                            $(info.el).css('cursor', 'default')
                            $(info.el).tooltip({
                                title: info.event.extendedProps.name,
                                container: 'body',
                                delay: {
                                    "show": 50,
                                    "hide": 50
                                }
                            });
                        }
                    },
                    eventTimeFormat: { // like '14:30:00'
                        hour: company.time_format == 'H:i' ? '2-digit' : 'numeric',
                        minute: '2-digit',
                        meridiem: company.time_format == 'H:i' ? false : true
                    }
                });

                if (calendarEl != null) {
                    calendar.render();
                }


                $('.cal-filter').on('click', function() {

                    var filter = [];

                    $('.filter-check:checked').each(function() {
                        filter.push($(this).val());
                    });

                    if (filter.length < 1) {
                        filter.push('None');
                    }

                    calendar.removeAllEventSources();
                    calendar.addEventSource({
                        url: "{{ route('dashboard.private_calendar') }}",
                        extraParams: {
                            filter: filter
                        }
                    });

                    filter = null;
                });

            })
        </script>
        <script>
            var initialLocaleCode = '{{ user()->locale }}';

            // Task Detail show in sidebar
            var getEventDetail = function(id, type) {
                if (type == 'ticket') {
                    var url = "{{ route('tickets.show', ':id') }}";
                    url = url.replace(':id', id);
                    window.location = url;
                    return true;
                }

                if (type == 'leave') {
                    return true;
                }

                openTaskDetail();

                switch (type) {
                    case 'task':
                        var url = "{{ route('tasks.show', ':id') }}";
                        break;
                    case 'event':
                        var url = "{{ route('events.show', ':id') }}";
                        break;
                    case 'holiday':
                        var url = "{{ route('holidays.show', ':id') }}";
                        break;
                    case 'leave':
                        var url = "{{ route('leaves.show', ':id') }}";
                        break;
                    default:
                        return 0;
                        break;
                }

                url = url.replace(':id', id);

                $.easyAjax({
                    url: url,
                    blockUI: true,
                    container: RIGHT_MODAL,
                    historyPush: true,
                    success: function(response) {
                        if (response.status == "success") {
                            $(RIGHT_MODAL_CONTENT).html(response.html);
                            $(RIGHT_MODAL_TITLE).html(response.title);
                        }
                    },
                    error: function(request, status, error) {
                        if (request.status == 403) {
                            $(RIGHT_MODAL_CONTENT).html(
                                '<div class="align-content-between d-flex justify-content-center mt-105 f-21">403 | Permission Denied</div>'
                            );
                        } else if (request.status == 404) {
                            $(RIGHT_MODAL_CONTENT).html(
                                '<div class="align-content-between d-flex justify-content-center mt-105 f-21">404 | Not Found</div>'
                            );
                        } else if (request.status == 500) {
                            $(RIGHT_MODAL_CONTENT).html(
                                '<div class="align-content-between d-flex justify-content-center mt-105 f-21">500 | Something Went Wrong</div>'
                            );
                        }
                    }
                });

            };

            // calendar filter
            var hideDropdown = false;

            $('#event-btn').click(function() {
                if (hideDropdown == true) {
                    $('#cal-drop').hide();
                    hideDropdown = false;
                } else {
                    $('#cal-drop').toggle();
                    hideDropdown = true;
                }
            });


            $(document).mouseup(e => {

                const $menu = $('.calendar-action');

                if (!$menu.is(e.target) && $menu.has(e.target).length === 0) {
                    hideDropdown = false;
                    $('#cal-drop').hide();
                }
            });
        </script>
    @endif

    <script>
        window.setInterval(function() {
            let date = new Date();
            $('#dashboard-clock').html(moment.tz(date, "{{ company()->timezone }}").format(MOMENTJS_TIME_FORMAT))
        }, 1000);

        $('#save-dashboard-widget').click(function() {
            $.easyAjax({
                url: "{{ route('dashboard.widget', 'private-dashboard') }}",
                container: '#privateDashboardWidgetForm',
                blockUI: true,
                type: "POST",
                redirect: true,
                data: $('#privateDashboardWidgetForm').serialize(),
                success: function() {
                    window.location.reload();
                }
            })
        });

        $('#clock-in').click(function() {
            const url = "{{ route('attendances.clock_in_modal') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('.request-shift-change').click(function() {
            var id = $(this).data('shift-schedule-id');
            var date = $(this).data('shift-schedule-date');
            var shiftId = $(this).data('shift-id');
            var url = "{{ route('shifts-change.edit', ':id') }}?date=" + date + "&shift_id=" + shiftId;
            url = url.replace(':id', id);

            $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_DEFAULT, url);
        });

        $('#view-shifts').click(function() {
            const url = "{{ route('employee-shifts.index') }}";
            $(MODAL_XL + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        @if (!is_null($currentClockIn))
            $('#clock-out').click(function() {

                var token = "{{ csrf_token() }}";
                var currentLatitude = document.getElementById("current-latitude").value;
                var currentLongitude = document.getElementById("current-longitude").value;

                $.easyAjax({
                    url: "{{ route('attendances.update_clock_in') }}",
                    type: "GET",
                    data: {
                        currentLatitude: currentLatitude,
                        currentLongitude: currentLongitude,
                        _token: token,
                        id: '{{ $currentClockIn->id }}'
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            window.location.reload();
                        }
                    }
                });
            });
        @endif

        $('.keep-open .dropdown-menu').on({
            "click": function(e) {
                e.stopPropagation();
            }
        });

        $('#weekly-timelogs').on('click', '.week-timelog-day', function() {
            var date = $(this).data('date');

            $.easyAjax({
                url: "{{ route('dashboard.week_timelog') }}",
                container: '#weekly-timelogs',
                blockUI: true,
                type: "POST",
                redirect: true,
                data: {
                    'date': date,
                    '_token': "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#weekly-timelogs').html(response.html)
                }
            })
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
    </script>

    @if (attendance_setting()->radius_check == 'yes' || attendance_setting()->save_current_location)
        <script>
            function setCurrentLocation() {
                const currentLatitude = document.getElementById("current-latitude");
                const currentLongitude = document.getElementById("current-longitude");

                function getLocation() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(showPosition);
                    }
                }

                function showPosition(position) {
                    currentLatitude.value = position.coords.latitude;
                    currentLongitude.value = position.coords.longitude;
                }
                getLocation();

            }

            setCurrentLocation();
        </script>
    @endif





    @include('dashboard.employee.section.instantdebitjs');




@endpush
