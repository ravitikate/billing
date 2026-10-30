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
        (!is_null($viewLeavePermission) && $viewLeavePermission != 'none')
    )
    @endif
@endpush

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<style>
    /* ===== Scrollbar CSS (X-axis only) ===== */
    * {
        scrollbar-width: thin;
        scrollbar-color: #8f54a0 #ffffff;
    }

    *::-webkit-scrollbar {
        height: 5px;
        width: 0;
    }

    *::-webkit-scrollbar-track {
        background: #ffffff;
    }

    *::-webkit-scrollbar-thumb {
        background-color: #8f54a0;
        border-radius: 2px;
        border: 3px solid #ffffff;
    }

    @media (max-width: 1366px) {
        .col-xl-6,
        .col-lg-6,
        .col-md-6,
        .col {
            flex: 1 1 100% !important;
            max-width: 100% !important;
        }

        .dt-container {
            overflow: auto;
        }

        .d-flex {
            flex-wrap: wrap;
            gap: 20px;
        }
    }

    .card-body.p-0.h-200 {
        min-height: 287px;
        padding: 10px !important;
    }

    .dt-layout-cell.dt-layout-start {
        display: none !important;
    }
    .dataTables_filter {
    display: block !important;
}
.colFlex {
    display: flex;
    justify-content: space-between;
    /* align-items: center; */
    /* flex-wrap: wrap; */
    gap: 5px;
}
.gap-2 {
    gap: 15px;
}
</style>

@php
    $billDetailCount = user()->permission('bill_detail_count');
    $pendingForAccountCount = user()->permission('pending_for_account_count');
    $billUnderProcessCount = user()->permission('bill_under_process_count');
    $paidVsUnpaidGraph = user()->permission('paid_vs_unpaid_graph');
    $totalVsPaidGraph = user()->permission('total_vs_paid_graph');
@endphp

@section('content')
<div class="px-4 py-2 border-top-0 emp-dashboard">
    @include('dashboard/common/head_details')
    
        @include('dashboard/employee/newTab/heroSection')

{{-- {{user()}} --}}
    {{-- <div class="colFlex mt-3">
        <div class=" mb-3">
                @include('dashboard/common/bill_details_count')
            </div>

        @if ($billDetailCount == 'all')
            <div class=" mb-3">
                @include('dashboard/common/count_bill_details')
            </div>
        @endif


        @if ($billUnderProcessCount == 'all')
            <div class=" mb-3">
                @include('dashboard/common/count_bill_under_prorocess')
            </div>
        @endif

          @if ($pendingForAccountCount == 'all')
            <div class=" mb-3">
                @include('dashboard/common/count_pending_for_account')
            </div>
        @endif
    </div> --}}


    <div class="row mt-3">
        @if ($paidVsUnpaidGraph == 'all')
            @include('dashboard/common/chart/totalVSpaidBill')
        @endif

        @if ($totalVsPaidGraph == 'all')
            @include('dashboard/common/chart/chartPaidVsUnpaidVsDelay')
        @endif
    </div>
</div>
<br>

<!-- Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('app.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="statusModalBody">
                <!-- Table content loaded dynamically -->
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {

  // Make it global
window.openStatusModal = function(type) {
     let url = '';
    let title = '';

    switch(type) {
        case 'intat':
            url = "{{ route('bills.status', 'intat') }}";
            title = "@lang('app.intatAccount')";
            break;
        case 'overshot':
            url = "{{ route('bills.status', 'overshot') }}";
            title = "@lang('app.overshotAccount')";
            break;
        case 'intatUnder':
            url = "{{ route('bills.status', 'intatUnder') }}";
            title = "@lang('app.intatUnderProcess')"; // make sure you have this lang
            break;
        case 'overshotUnder':
            url = "{{ route('bills.status', 'overshotUnder') }}";
            title = "@lang('app.overshotUnderProcess')"; // make sure you have this lang
            break;
        case 'intatStatus':
            url = "{{ route('bills.status', 'intatStatus') }}";
            title = "@lang('app.intatStatus')"; // make sure you have this lang
            break;
        case 'overshotStatus':
            url = "{{ route('bills.status', 'overshotStatus') }}";
            title = "@lang('app.overshotStatus')"; // make sure you have this lang
            break;
             case 'billStatus':
            url = "{{ route('bills.status', 'billStatus') }}";
            title = "@lang('app.billStatus')"; // make sure you have this lang
            break;
             case 'withAccount':
                        url = "{{ route('bills.status', 'withAccount') }}";
                        title = "@lang('app.withAccount')";
                        break;
        default:
            console.error("Invalid type passed to openStatusModal:", type);
            return;
    }

    $('#statusModalLabel').text(title);
    $('#statusModalBody').html('<p class="text-center">Loading...</p>');
    $('#statusModal').modal('show');

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#statusModalBody').html(response);

            let $table = $('#statusModalBody').find('table').first();
            if (!$table.length) return console.error("No table found");

            if ($.fn.dataTable && $.fn.dataTable.isDataTable($table)) {
                $table.DataTable().clear().destroy();
            }

            $table.DataTable({
                responsive: true,
                autoWidth: false,
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthMenu: [10, 25, 50, 100],
                dom: 'lfrtip',
                language: {
                    search: "Search:",
                    paginate: { previous: "<", next: ">" },
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });

            $('#statusModal').on('shown.bs.modal', function() {
                $table.DataTable().columns.adjust().responsive.recalc();
            });
        },
        error: function() {
            $('#statusModalBody').html('<p class="text-danger text-center">Error loading data</p>');
        }
    });
};




});

</script>
<script>

// ===== Chart Functions =====
    function renderBarChart(chartType, ctxId, labels, datasets, yAxisLabel, stacked) {
        const ctx = document.getElementById(ctxId).getContext('2d');
        new Chart(ctx, {
            type: chartType,
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { weight: 'bold', size: 10 },
                        formatter: value => value > 0 ? value.toFixed(2) : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ₹ ${tooltipItem.raw.toFixed(2)} Cr`;
                            }
                        }
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, stacked, title: { display: true, text: yAxisLabel }, grid: { display: false } },
                    x: { stacked, grid: { display: false } }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    function renderBarChart1(chartType, ctxId, labels, datasets, yAxisLabel, stacked) {
        const ctx = document.getElementById(ctxId).getContext('2d');
        new Chart(ctx, {
            type: chartType,
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { weight: 'bold', size: 10 },
                        formatter: value => value > 0 ? value.toFixed(2) : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw.toFixed(2)}`;
                            }
                        }
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, stacked, title: { display: true, text: yAxisLabel }, grid: { display: false } },
                    x: { stacked, grid: { display: false } }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    // ===== Render Charts =====
    Chart.register(ChartDataLabels);

    renderBarChart(
        'bar',
        'totalVSpaidBill',
        @json($chartLabels),
        [
            { label: 'Due + Paid (Total)', data: @json($chartTotalCombined), backgroundColor: '#007bff', borderColor: '#007bff', borderWidth: 1 },
            { label: 'Total Paid', data: @json($chartPaidAmounts), backgroundColor: '#28a745', borderColor: '#28a745', borderWidth: 1 },
            { label: 'Total Due', data: @json($chartTotalAmounts), backgroundColor: '#ffc107', borderColor: '#ffc107', borderWidth: 1 }
        ],
        'Amount in Crores (₹)',
        false
    );

    renderBarChart1(
        'bar',
        'chartPaidVsUnpaidVsDelay',
        @json($chartPaidVsUnpaidVsDelay['labels']),
        [
            { label: 'Paid Bills', data: @json($chartPaidVsUnpaidVsDelay['paid']), backgroundColor: '#28a745', borderColor: '#28a745', borderWidth: 1 },
            { label: 'Unpaid Bills', data: @json($chartPaidVsUnpaidVsDelay['unpaid']), backgroundColor: '#007bff', borderColor: '#007bff', borderWidth: 1 },
            { label: 'Delayed Bills', data: @json($chartPaidVsUnpaidVsDelay['delayed']), backgroundColor: '#f96060', borderColor: '#f96060', borderWidth: 1 }
        ],
        'Count of Bills',
        false
    );

    // ===== Scroll to Section =====
    window.scrollToSection = function(sectionId) {
        const target = document.getElementById(sectionId);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    };
</script>

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

@include('dashboard.employee.section.addinstantDebitAmount')
@include('dashboard.employee.section.instantdebitjs')
@endpush
