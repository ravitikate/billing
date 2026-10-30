@extends('layouts.app')

@push('styles')
    @if ((!is_null($viewEventPermission) && $viewEventPermission != 'none')
        || (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none')
        || (!is_null($viewTaskPermission) && $viewTaskPermission != 'none')
        || (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none')
        || (!is_null($viewLeavePermission) && $viewLeavePermission != 'none')
        )
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

        .fc-list-event-graphic{
            display: none;
        }

        .fc .fc-list-event:hover td{
            background-color: #fff !important;
            color:#000 !important;
        }
        .left-3{
            margin-right: -22px;
        }
        .clockin-right{
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
        <div class="d-lg-flex d-md-flex d-block py-2 pb-2 align-items-center">
            <div>
                <h3 class="heading-h3 mb-0 f-21 font-weight-bold">@lang('app.welcome') {{ $user->name }}</h3>
            </div>
            <!-- WELOCOME NAME END -->

            <!-- CLOCK IN CLOCK OUT START -->
            <div
                class="ml-auto d-flex clock-in-out mb-3 mb-lg-0 mb-md-0 m mt-4 mt-lg-0 mt-md-0 justify-content-end">
                <p
                    class="mb-0 text-lg-right text-md-right f-18 font-weight-bold text-dark-grey d-grid align-items-center">
                    <input type="hidden" id="current-latitude" name="current_latitude">
                    <input type="hidden" id="current-longitude" name="current_longitude">

                    <span id="dashboard-clock">{!! now()->timezone(company()->timezone)->translatedFormat(company()->time_format) . '</span><span class="f-10 font-weight-normal">' . now()->timezone(company()->timezone)->translatedFormat('l') . '</span>' !!}


                </p>
                @php
                    $currentDateTime = now()->timezone(company()->timezone);
                    $msg = null;
                @endphp

                @if (in_array('attendance', user_modules()))
                    @if (is_null($currentClockIn) && $checkJoiningDate == true || (is_null($currentClockIn) && $flagbtn == false))
                        <button type="button" class="btn-primary rounded f-15 ml-4" id="clock-in"
                            @if (($cannotLogin || !is_null($msg)) && ($user->isAdmin($user->id) || $user->isEmployee($user->id)))
                                disabled
                                data-toggle="tooltip" data-placement="top" title="{{__($msg)}}"
                            @endif
                        ><i
                        class="icons icon-login mr-2"></i>@lang('modules.attendance.clock_in')</button>
                    @endif
                @endif

                @if (!is_null($currentClockIn) && is_null($currentClockIn->clock_out_time) || (!is_null($currentClockIn) && $flagbtn == true))
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
                                    <i class="fa fa-cog" data-original-title="{{__('modules.dashboard.dashboardWidgetsSettings')}}" data-toggle="tooltip"></i>
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
                                                    value="true" @if ($widget->status) checked @endif type="checkbox">
                                                <label for="{{ $widget->widget_name }}">@lang('modules.dashboard.' .
                                                    $wname)</label>
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
            @if(count(array_intersect(['profile', 'shift_schedule', 'birthday', 'notices'], $activeWidgets)) > 0)
                <div class="col-xl-7 col-lg-12 col-md-12 e-d-info-notices">
                    <div class="row">
                        <div class="col-md-6">
                        @if (in_array('profile', $activeWidgets))
                        <!-- EMP DASHBOARD INFO START -->
                            <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                                <a @if(!in_array('client', user_roles())) href="{{ route('employees.show', user()->id) }}" @endif >
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
                        <!-- EMP DASHBOARD INFO END -->
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div
                            class="bg-white p-3 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                            <div class="d-block ">
                                <h5 class="f-13 f-w-500 mb-20 text-darkest-grey">@lang('modules.dashboard.contracts')</h5>
                                <div class="d-flex">
                                     <a href="{{ route('contracts.index') }}?signed=yes">
                                        <p class="mb-0 f-15 font-weight-bold text-blue d-grid mr-5">
                                            <span id="total_signs">10</span><span
                                                class="f-12 font-weight-normal text-lightest">
                                                @lang('modules.dashboard.contractsSigned')</span>
                                        </p>
                                    </a>
                                    <a href="{{ route('contracts.index') }}?signed=no">
                                        <p class="mb-0 f-15 font-weight-bold text-red d-grid ">
                                            <span id="total_contract">10</span>
                                            <span class="f-12 font-weight-normal text-lightest">
                                                @lang('modules.dashboard.unSignedContracts') </span>
                                        </p>
                                    </a>
                                   
                                </div>
                            </div>
                            <div class="d-block">
                                <i class="fa fa-file-invoice text-lightest f-18"></i>
                            </div>
                        </div>
                    </div>
                        <div class="col-sm-12 col-lg-12 mt-4" >
                            <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                                <x-cards.data :title="__('modules.tasks.contractList')" padding="false" otherClasses="h-200">
                                    <x-table>
                                        <x-slot name="thead">
                                            <th>@lang('app.contractNo')#</th>
                                            <th>@lang('app.contractName')</th>
                                            <th>@lang('app.contractorName')</th>
                                            <th class="text-right pr-20">@lang('app.status')</th>
                                        </x-slot>

                                        @forelse ($contracts as $task)
                                            <tr>
                                                <td class="pl-20">
                                                    <a href="javascript:;"
                                                       data-contract-id="{{ $task->id }}"
                                                       class="redirectContract openRightModal f-12 mb-1 text-darkest-grey">
                                                        {{ $task->contract_number }}
                                                    </a>
                                                </td>
                                                <td class="pl-20">
                                                    <a href="javascript:;"
                                                       data-contract-id="{{ $task->id }}"
                                                       class="redirectContract openRightModal f-12 mb-1 text-darkest-grey">
                                                        {{ $task->subject }}
                                                    </a>
                                                </td>
 
                                                <td class="pr-20" align="left">
                                                    {{ $task->name }}
                                                </td>

                                                <td class="pr-20" align="right">
                                                    {{ ucfirst($task->contract_status) }}
                                                </td>


                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="shadow-none">
                                                    <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')"/>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </x-table>
                                </x-cards.data>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <!-- EMP DASHBOARD INFO NOTICES END -->
            <!-- EMP DASHBOARD TASKS PROJECTS EVENTS START -->
            <div class="col-xl-5 col-lg-12 col-md-12 e-d-tasks-projects-events">
                <!-- EMP DASHBOARD TASKS PROJECTS START -->
                <div class="row mb-3 mt-xl-0 mt-lg-8 mt-md-8 mt-4">


                    <div class="col-xl-8 col-lg-12 col-md-12">



                        <div
                        class="bg-white p-3 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                        <div class="d-block ">
                            <h5 class="f-13 f-w-500 mb-20 text-darkest-grey">@lang('modules.dashboard.contractsAmount')</h5>
                            <div class="d-flex">
                                <a href="{{ route('contracts.index') }}?signed=yes">
                                    <p class="mb-0 f-15 font-weight-bold text-blue d-grid mr-5">
                                        <span id="total_signed_amount">10</span><span class="f-12 font-weight-normal text-lightest">
                                            @lang('modules.dashboard.signedAmount') </span>
                                    </p>
                                </a>

                                <a href="{{ route('contracts.index') }}?signed=no">
                                    <p class="mb-0 f-15 font-weight-bold text-red d-grid">
                                        <span id="total_unsigned_amount">10</span><span
                                            class="f-12 font-weight-normal text-lightest">
                                            @lang('modules.dashboard.unsignedAmount')</span>
                                    </p>
                                </a>
                            </div>
                        </div>
                        <div class="d-block">
                            <i class="fa fa-file-invoice text-lightest f-18"></i>
                        </div>
                    </div>
                    </div>

                </div>

            </div>
            <!-- EMP DASHBOARD TASKS PROJECTS EVENTS END -->
        </div>
        <!-- EMPLOYEE DASHBOARD DETAIL END -->

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @if ((!is_null($viewEventPermission) && $viewEventPermission != 'none')
        || (!is_null($viewHolidayPermission) && $viewHolidayPermission != 'none')
        || (!is_null($viewTaskPermission) && $viewTaskPermission != 'none')
        || (!is_null($viewTicketsPermission) && $viewTicketsPermission != 'none')
        || (!is_null($viewLeavePermission) && $viewLeavePermission != 'none')
        )
        <script src="{{ asset('vendor/full-calendar/main.min.js') }}"  defer="defer"></script>
        <script src="{{ asset('vendor/full-calendar/locales-all.min.js') }}"  defer="defer"></script>

    @endif

    <script>
        window.setInterval(function () {
            let date = new Date();
            $('#dashboard-clock').html(moment.tz(date, "{{ company()->timezone }}").format(MOMENTJS_TIME_FORMAT))
        }, 1000);
        $('#clock-in').click(function() {
            const url = "{{ route('attendances.clock_in_modal') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        @if (!is_null($currentClockIn))
            $('#clock-out').click(function() {
                var url = "{{ route('attendances.show_clocked_hours') }}?aid={{ $currentClockIn->id }}";

                $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
                $.ajaxModal(MODAL_LG, url);

            });

            function clockOut()
            {

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
            }
        @endif

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

        function fetchTaskCount() {
            $.ajax({
                url: '{{ route('invoice.contractCount') }}',
                type: "GET",
                dataType: "json",
                success: function(response) {
                    console.log("Response:", response);
                    $("#total_contract").text(response.total_contract ?? 0);
                    $("#total_signs").text(response.total_signs ?? 0);
                    $("#total_signed_amount").text(response.total_signed_amount ?? 0);
                    $("#total_unsigned_amount").text(response.total_unsigned_amount ?? 0);

                },
                error: function(xhr) {
                    console.error("Error fetching invoice count:", xhr.responseText);
                }
            });
        }
        $(document).ready(function () {
            fetchTaskCount();
            setInterval(fetchTaskCount, 60000); // Refresh every 60 seconds
        });

        $(document).on('click', '.redirectContract', function() {
            var contractId = $(this).data('contract-id'); // Get contract ID from data attribute
            var url = `{{ route('contracts.show', ':contractId') }}`.replace(':contractId', contractId);
            window.location.href = url;
        });


    </script>


@endpush
