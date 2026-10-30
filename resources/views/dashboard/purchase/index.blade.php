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
        select#dt-length-0 {
            display: none;
        }

        select#dt-length-1 {
            display: none;
        }

        select#dt-length-2 {
            display: none;
        }

        select#dt-length-3 {
            display: none;
        }

        label {
            display: none;
        }

        div#paid_info {
            display: none;
        }

        div#due_info {
            display: none;
        }

        div#intat_info {
            display: none;
        }

        div#overshot_info {
            display: none;
        }

        .card-body.p-0.h-200 {
            min-height: 287px;
            padding: 10px !important;
        }

        button.dt-paging-button {
            font-size: 10px;
            font-weight: bold;
            /* padding: 5px !important; */
        }

        button.dt-paging-button.current {
            background: #1d82f5 !important;
            color: #fff !important;
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
                            @lang('app.po_detail')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('work_order.work-order') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalWO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.totalwork_order')</span>
                                </p>
                            </a>

                            <a href="{{ route('bills.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                    {{ $totalValidateWO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.validate_wo') </span>
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
        <div class="row">
            <div class="col-sm-12  mt-3">
                <x-cards.data>
                    @slot('title')
                        {{ __('app.totalPOvalidate') }}
                        <button onclick="downloadChart('budgetChart')" class="btn btn-sm btn-light"
                            style="float:right; padding:0 5px;" title="Download Chart">
                            <i class="fa fa-download"></i>
                        </button>
                    @endslot
                    <canvas id="budgetChart" style="height: 200px !important"></canvas>
                </x-cards.data>
            </div>
        </div>
    </div>
@endsection


<script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
<script src="{{ asset('vendor/jquery/Chart.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
@push('scripts')
    <script>
        function downloadChart(canvasId) {
            const canvas = document.getElementById(canvasId);

            // Traverse up to find the title
            let parent = canvas.closest('.card') || canvas.closest('[class*="x-cards"]');
            let title = 'chart';

            if (parent) {
                const possibleTitles = parent.querySelectorAll('h4, h5, .card-title');
                if (possibleTitles.length > 0) {
                    title = possibleTitles[0].textContent.trim();
                }
            }

            // Clean up the title for filename use
            title = title.replace(/[\\/:*?"<>|]/g, '').replace(/\s+/g, '_');

            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = title + '.png';
            link.click();
        }
    </script>

    <script>
        const labels = @json($poData['labels']);
        const committedData = @json($poData['values'][0]);
        const actualData = @json($poData['values'][1]);

        Chart.register(ChartDataLabels);

        const ctx = document.getElementById('budgetChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Total Amount (Cr)',
                        data: committedData,
                        backgroundColor: '#6f42c1',
                        borderColor: '#6f42c1',
                        borderWidth: 1
                    },
                    {
                        label: 'Validated Amount (Cr)',
                        data: actualData,
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 9
                        },
                        formatter: (value) => value > 0 ? value.toFixed(2) : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ₹ ${tooltipItem.raw.toFixed(2)} Cr`;
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: false,
                        title: {
                            display: true,
                            text: 'Amount in Crores (₹)'
                        },
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        stacked: false,
                        grid: {
                            display: false
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    </script>
@endpush
