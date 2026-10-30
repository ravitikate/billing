@extends('layouts.app')




@section('content')
{{-- {{dd($budget_details)}} --}}
    <div class="content-wrapper border-top-0 client-detail-wrapper">
        <style>
            #logo {
                height: 50px;
            }

            #signatory img {
                height: 95px;
                margin-bottom: -40px;
                margin-top: 5px;
                margin-right: 15px;
            }
            .bg-dark {
    background-color: #4f7db7cc !important;
}
        </style>

        <div class="card  invoice">
            <!-- CARD BODY START -->
            <div class="add-client bg-white rounded">


            <div class="card-body">
                <h5>{{__('app.project')}}: {{$projectDetails->project_name}}</h5>
                <h5>{{__('app.subProject')}}: {{$projectDetails->task_info}}</h5>
                <br>
                 
<div class="row mb-3">
    @php
        $groupedData = collect($merged_data ?? [])->groupBy('type');

        $typeLabels = [
            'build' => 'Building',
            'dev' => 'Development',
            'tech' => 'Technical',
        ];
    @endphp

    <div class="col-sm-12">
        <h4 class="f-18 f-w-500 mb-4">Budget Details</h4>
    </div>
    
    <div class="col">
        <table class="table table-bordered">
            <thead class="bg-light-grey text-dark-grey font-weight-bold">
                <tr>
                    <th>Budget Head Name</th> 
                    <th class="text-end">Budget Amount</th>
                    <th class="text-end">Committed (PO + WO)</th>
                    <th class="text-end">Actual</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotalBudget = 0;
                    $grandTotalCommitted = 0;
                    $grandTotalActual = 0;
                @endphp

                @if ($groupedData->isEmpty())
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            No budget details available.
                        </td>
                    </tr>
                @else
                    @foreach ($groupedData as $type => $items)
                        <!-- Group Heading Row -->
                        <tr>
                            <td colspan="4" class="text-center font-weight-bold bg-secondary text-white">
                                {{ $typeLabels[$type] ?? ucfirst($type) }} Totals
                            </td>
                        </tr>

                        @php
                            $subTotalBudget = 0;
                            $subTotalCommitted = 0;
                            $subTotalActual = 0;
                        @endphp

                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item['name'] ?? '-' }}</td> 
                                <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['budget_cost'] ?? 0)) }}</td>
                                <td class="text-end">
                                    @if (!empty($item['total_amount']) && $item['total_amount'] > 0)
                                        <a href="{{ route('committed.breakup', ['wbs' => $item['wbs'], 'budgetId' => $budget_head_id]) }}">
                                            ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['total_amount'])) }}
                                        </a>
                                    @else
                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(0) }}
                                    @endif
                                </td>
                                
                                <td class="text-end">
                                    @if (!empty($item['actual_amount']) && $item['actual_amount'] > 0)
                                        <a href="{{ route('actual.breakup', ['wbs' => $item['wbs'], 'budgetId' => $budget_head_id]) }}">
                                            ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['actual_amount'])) }}
                                        </a>
                                    @else
                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(0) }}
                                    @endif
                                </td>

                                 
                            </tr>

                            @php
                                $subTotalBudget += $item['budget_cost'] ?? 0;
                                $subTotalCommitted += $item['total_amount'] ?? 0 ;
                                $subTotalActual += $item['actual_amount'] ?? 0 ;
                            @endphp
                        @endforeach

                        <!-- Subtotal Row -->
                        <tr class="bg-light text-dark font-weight-bold">
                             <td class="text-end">Total {{ $typeLabels[$type] ?? ucfirst($type) }}</td>
                            <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($subTotalBudget)) }}</td>
                            <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($subTotalCommitted)) }}</td>
                            <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($subTotalActual)) }}</td>
                        </tr>

                        @php
                            $grandTotalBudget += $subTotalBudget;
                            $grandTotalCommitted += $subTotalCommitted;
                            $grandTotalActual += $subTotalActual;
                        @endphp
                    @endforeach

                    <!-- Grand Total Row -->
                    <tr class="bg-dark text-white font-weight-bold">
                        <td class="text-end">Grand Total</td>
                        <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($grandTotalBudget)) }}</td>
                        <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($grandTotalCommitted)) }}</td>
                        <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($grandTotalActual)) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>



            

                </div>
            </div>
        </div>
 
        
        <div class="row">
           
            
            <div class="col-sm-12 col-lg-9 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.subbudgetvsactualvspo') }} &nbsp;&nbsp;
                <button onclick="downloadChart('budgetChart')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="budgetChart" width="400" height="200"></canvas>
        </x-cards.data>
    </div>
    <div class="col-sm-12 col-lg-3 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.subTbudgetvsactualvspo') }}
                <button onclick="downloadChart('totalBudgetChart')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="totalBudgetChart" height="500"></canvas>
        </x-cards.data>
    </div>
    
     
            
        </div>

    </div>
    </div>
 
@endsection 
 
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
 
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = @json($graphTotalWBS['labels']);
    const budgetData = @json($graphTotalWBS['values'][0]);
    const poData = @json($graphTotalWBS['values'][1]);
    const actualData = @json($graphTotalWBS['values'][2]);

    Chart.register(ChartDataLabels);

    const ctx = document.getElementById('budgetChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData,
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'PO',
                    data: poData,
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Actual',
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
                    font: {
                        weight: 'bold',
                        size: 10
                    },
                    rotation: 90,
                    offset: 5,
                    padding: 4,
                    formatter: value => value.toFixed(2)
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `${tooltipItem.dataset.label}: ₹ ${tooltipItem.raw.toFixed(2)} Cr`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount in Crores (₹)'
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalBudget = @json($graphTotal['values'][0][0]);
    const totalAmountPlusPoTax = @json($graphTotal['values'][1][0]);
    const actualTotalPlusActualTax = @json($graphTotal['values'][2][0]);
    const totalBudgetLabels = @json($graphTotal['labels']);

    const scaleToCrores = 1;

    const totalBudgetInCrores = totalBudget / scaleToCrores;
    const totalAmountPlusPoTaxInCrores = totalAmountPlusPoTax / scaleToCrores;
    const actualTotalPlusActualTaxInCrores = actualTotalPlusActualTax / scaleToCrores;

    Chart.register(ChartDataLabels);

    const ctx1 = document.getElementById('totalBudgetChart').getContext('2d');

    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: totalBudgetLabels,
            datasets: [{
                label: 'Budget vs Actual Comparison',
                data: [
                    totalBudgetInCrores,
                    totalAmountPlusPoTaxInCrores,
                    actualTotalPlusActualTaxInCrores
                ],
                backgroundColor: ['#007bff', '#ffc107', '#28a745'],
                borderColor: ['#007bff', '#ffc107', '#28a745'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount in Crores (₹)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value !== 0
                                ? '₹ ' + value.toFixed(0) + ' Cr'
                                : '₹ 0 Cr';
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
            display: false // <-- Disable the legend here
        },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `${tooltipItem.dataset.label}: ₹ ${tooltipItem.raw.toFixed(2)} Cr`;
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 10
                    },
                    rotation: 360,
                    formatter: (value) => '₹ ' + value.toFixed(2) + ' Cr',
                    offset: 10,
                    padding: 4
                }
            }
        },
        plugins: [ChartDataLabels]
    });
});
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




