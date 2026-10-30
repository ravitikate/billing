<style>
    tr.bg-dark.text-white.font-weight-bold {
    background-color: #4f6cc1ba !important;
}
</style>

@php
    $editProjectPermission = user()->permission('edit_projects');
    $addPaymentPermission = user()->permission('add_payments');
    $projectBudgetPermission = user()->permission('view_project_budget');
    $memberIds = $project->members->pluck('user_id')->toArray();
@endphp

<div class="d-lg-flex">
    <div class="w-100 py-0 py-lg-3 py-md-0 ">
        <div class="d-flex align-content-center flex-lg-row-reverse mb-4">
            @if (!$project->trashed())
                <div class="ml-lg-3 ml-md-0 ml-0 mr-3 mr-lg-0 mr-md-3" hidden>
                    @if (
                        $editProjectPermission == 'all' ||
                            ($editProjectPermission == 'added' && $project->added_by == user()->id) ||
                            $project->project_admin == user()->id)
                        <select class="form-control select-picker change-status height-35">
                            @foreach ($projectStatus as $status)
                                <option
                                    data-content="<i class='fa fa-circle mr-1 f-15' style='color:{{ $status->color }}'></i>{{ $status->status_name }}"
                                    @if ($project->status == $status->status_name)
                                    selected
                            @endif
                            value="{{ $status->status_name }}"> {{ $status->status_name }}
                            </option>
                    @endforeach
                    </select>
                @else
                    @foreach ($projectStatus as $status)
                        @if ($project->status == $status->status_name)
                            <div class="bg-white p-2 border rounded">
                                <i class='fa fa-circle mr-2'
                                    style="color:{{ $status->color }}"></i>{{ $status->status_name }}
                            </div>
                        @endif
                    @endforeach
            @endif
        </div>

        <div class="ml-lg-3 ml-md-0 ml-0 mr-3 mr-lg-0 mr-md-3" hidden>
            <div class="dropdown">
                <button
                    class="btn btn-lg bg-white border height-35 f-15 px-2 py-1 text-dark-grey text-capitalize rounded  dropdown-toggle"
                    type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    @lang('app.action') <i class="icon-options-vertical icons"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                    aria-labelledby="dropdownMenuLink" tabindex="0">

                    @if (
                        $editProjectPermission == 'all' ||
                            $project->project_admin == user()->id ||
                            ($editProjectPermission == 'added' && user()->id == $project->added_by) ||
                            ($editProjectPermission == 'owned' && user()->id == $project->client_id && in_array('client', user_roles())) ||
                            ($editProjectPermission == 'owned' && in_array(user()->id, $memberIds) && in_array('employee', user_roles())) ||
                            ($editProjectPermission == 'both' && (user()->id == $project->client_id || user()->id == $project->added_by)) ||
                            ($editProjectPermission == 'both' && in_array(user()->id, $memberIds) && in_array('employee', user_roles())))
                        <a class="dropdown-item openRightModal"
                            href="{{ route('projects.edit', $project->id) }}">@lang('app.editProject')
                        </a>

                        <a class="dropdown-item" href="{{ route('front.gantt', $project->hash) }}" target="_blank">
                            @lang('modules.projects.viewPublicGanttChart')
                        </a>

                        <a class="dropdown-item"
                            href="{{ url()->temporarySignedRoute('front.taskboard', now()->addDays(\App\Models\GlobalSetting::SIGNED_ROUTE_EXPIRY), $project->hash) }}"
                            target="_blank">
                            @lang('app.publicTaskBoard')
                        </a>
                        <hr class="my-1">
                    @endif

                    @php $projectPin = $project->pinned() @endphp

                    @if ($projectPin)
                        <a class="dropdown-item" href="javascript:;" id="pinnedItem"
                            data-pinned="pinned">@lang('app.unpinProject')
                        </a>
                    @else
                        <a class="dropdown-item" href="javascript:;" id="pinnedItem"
                            data-pinned="unpinned">@lang('app.pinProject')
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if ($projectPin)
            <div class="align-self-center">
                <span class='badge badge-success'><i class='fa fa-thumbtack'></i> @lang('app.pinned')</span>
            </div>
        @endif
    @elseif(
        $editProjectPermission == 'all' ||
            $project->project_admin == user()->id ||
            ($editProjectPermission == 'added' && user()->id == $project->added_by) ||
            ($editProjectPermission == 'owned' && user()->id == $project->client_id && in_array('client', user_roles())) ||
            ($editProjectPermission == 'owned' && in_array(user()->id, $memberIds) && in_array('employee', user_roles())) ||
            ($editProjectPermission == 'both' && (user()->id == $project->client_id || user()->id == $project->added_by)) ||
            ($editProjectPermission == 'both' && in_array(user()->id, $memberIds) && in_array('employee', user_roles())))
        <div class="ml-3">
            <x-forms.button-primary class="restore-project" icon="undo">@lang('app.unarchive')
            </x-forms.button-primary>
        </div>
        @endif
    </div>
    <!-- PROJECT PROGRESS AND CLIENT START -->
    <div class="row">
        <!-- PROJECT PROGRESS START -->
        <div class="col-md-3 mb-4">
            <x-cards.data :title="__('modules.projects.projectProgress')"
                otherClasses="d-flex d-xl-flex d-lg-block d-md-flex  justify-content-between align-items-center">
 
                <!-- PROGRESS START DATE START -->
                <div class="p-start-date mb-xl-0 mb-lg-3">
                    <h5 class="text-lightest f-14 font-weight-normal">@lang('app.startDate')</h5>
                    <p class="f-15 mb-0">{{ $project->start_date->translatedFormat(company()->date_format) }}</p>
                </div>
                 

            </x-cards.data>
        </div>
        <!-- PROJECT PROGRESS END -->
        <!-- CLIENT START -->
        <div class="col col-md-3 mb-2">
            @if (!is_null($project->client) && in_array('clients', user_modules()))
                <x-cards.data :title="__('app.client')"
                    otherClasses="d-block d-xl-flex d-lg-block d-md-flex  justify-content-between align-items-center">

                    <div class="p-client-detail">
                        <div class="card border-0 ">
                            <div class="card-horizontal">

                                <div class="card-img m-0">
                                    <img class="" src=" {{ $project->client->image_url }}"
                                        alt="{{ $project->client->name_salutation }}">
                                </div>
                                <div class="card-body border-0 p-0 ml-4 ml-xl-4 ml-lg-3 ml-md-3">
                                    <h4 class="card-title f-15 font-weight-normal mb-0">
                                        @if (!in_array('client', user_roles()))
                                            <a href="{{ route('clients.show', $project->client_id) }}"
                                                class="text-dark">
                                                {{ $project->client->name_salutation }}
                                            </a>
                                        @else
                                            {{ $project->client->name_salutation }}
                                        @endif
                                    </h4>
                                    <p class="card-text f-14 text-lightest mb-0">
                                        {{ $project->client->clientDetails->company_name }}
                                    </p>
                                    @if ($project->client->country_id)
                                        <span
                                            class="card-text f-12 text-lightest text-capitalize d-flex align-items-center">
                                            <span
                                                class='flag-icon flag-icon-{{ strtolower($project->client->country->iso) }} mr-2'></span>
                                            {{ $project->client->country->nicename }}
                                        </span>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>

                    @if (
                        (in_array('admin', user_roles()) && $messageSetting->allow_client_admin == 'yes') ||
                            (in_array('employee', user_roles()) && $messageSetting->allow_client_employee == 'yes'))
                        <div class="p-client-msg mt-4 mt-xl-0 mt-lg-4 mt-md-0">
                            <button type="button" class="btn-secondary rounded f-15" id="new-chat"
                                data-client-id="{{ $project->client->id }}"> <i class="fab fa-whatsapp mr-1"></i>
                                @lang('app.message')</button>
                        </div>
                    @endif

                </x-cards.data>
            @else
                <x-cards.data
                    otherClasses="d-flex d-xl-flex d-lg-block d-md-flex  justify-content-between align-items-center">
                    <x-cards.no-record icon="user" :message="__('messages.noClientAddedToProject')" />
                </x-cards.data>
            @endif
        </div>
        @if ($projectBudgetPermission == 'all')
        <div class="col col-md-6">
            <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                <div class="d-block text-capitalize">
                    <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                        @lang('modules.projects.budget') </h5>
                    <div class="d-flex">
                          <a href="{{ route('projects.index') }}">
                            <p class="mb-0 f-16 font-weight-bold text-blue d-grid mr-5">
                                ₹ {{\App\Helper\NumberFormat::formatIndianCurrency(round($total_costing['total_budget_cost'] ?? 0))  }}<span
                                    class="f-12 font-weight-normal text-lightest">
                                    @lang('modules.projects.projectBudget')</span>
                            </p>
                        </a>
                        <a href="{{ route('projects.index') }}">
                            <p class="mb-0 f-16 font-weight-bold text-blue d-grid mr-5">
                                ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round(($total_costing['total_committed_cost'] ?? 0))) }} 


                                <span class="f-12 font-weight-normal text-lightest">
                                    {{ __('app.committedAmt') }}
                                </span>
                            </p>
                        </a>
                        <a href="{{ route('projects.index') }}">
                            <p class="mb-0 f-16 font-weight-bold text-danger d-grid mr-5">
                                ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round(($total_costing['total_actual_cost'] ?? 0)  )) }} 
 

                                <span class="f-12 font-weight-normal text-lightest">
                                    {{ __('app.actualAmt') }} </span>
                            </p>
                        </a>
                    </div>
                    <br>
                </div>

                <div class="d-block">
                    <i class="fa fa-coins text-lightest f-18"></i>
                </div>
            </div>


        </div> 
        @endif
        <!-- CLIENT END -->
    </div>
     

    <div class="row mb-3">
        @php
            $groupedData = collect($totalWBSData)->groupBy('type');

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

                @foreach ($groupedData as $type => $items)
                    <!-- Group Heading Row -->
                    <tr>
                        <td colspan="4" class="text-center font-weight-bold bg-secondary text-white">
                            {{ $typeLabels[$type] ?? ucfirst($type) }} Total
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
                            <td class="text-end">₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['total_budget_cost'] ?? 0)) }}</td> 
                           <td class="text-end">
                                @if (!empty($item['committed_cost']) && $item['committed_cost'] > 0)
                                    <a href="{{ route('committed-project.breakup', ['project_id' => $item['project_id'], 'wbs' => $item['wbs_suffix']]) }}">
                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['committed_cost'])) }}
                                    </a>
                                @else
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(0) }}
                                @endif
                            </td>
                            
                            <td class="text-end">
                                @if (!empty($item['actual_cost']) && $item['actual_cost'] > 0)
                                    <a href="{{ route('actual-project.breakup', ['project_id' => $item['project_id'], 'wbs' => $item['wbs_suffix']]) }}">
                                        ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($item['actual_cost'])) }}
                                    </a>
                                @else
                                    ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(0) }}
                                @endif
                            </td>

                           
                        </tr>

                        @php
                            $subTotalBudget += $item['total_budget_cost'] ?? 0;
                            $subTotalCommitted += $item['committed_cost'] ?? 0;
                            $subTotalActual += $item['actual_cost'] ?? 0;
                        @endphp
                    @endforeach

                    <!-- Subtotal Row -->
                    <tr class="bg-light text-dark font-weight-bold">
                        <td class="text-end">Total {{ $typeLabels[$type] }}</td>
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
            </tbody>
        </table>
    </div>
    </div>

    <div class="row">
    <div class="col-sm-12 col-lg-9 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.budgetvsactualvspo') }} &nbsp;&nbsp;
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
                {{ __('app.totalProjectGraph') }}
                <button onclick="downloadChart('totalBudgetChart')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="totalBudgetChart" height="500"></canvas>
        </x-cards.data>
    </div>

    <div class="col-sm-12 col-lg-9 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.budgetvsactualvspoBuildiing') }} &nbsp;&nbsp;
                <button onclick="downloadChart('budgetChart2')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="budgetChart2" width="400" height="200"></canvas>
        </x-cards.data>
    </div>

    <div class="col-sm-12 col-lg-3 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.budgetvsactualvspoBuiT') }}
                <button onclick="downloadChart('totalBudgetChart3')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="totalBudgetChart3" height="500"></canvas>
        </x-cards.data>
    </div>

    <div class="col-sm-12 col-lg-9 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.budgetvsactualvspoDev') }}&nbsp;&nbsp;
                <button onclick="downloadChart('budgetChart4')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="budgetChart4" width="400" height="200"></canvas>
        </x-cards.data>
    </div>

    <div class="col-sm-12 col-lg-3 mt-3">
        <x-cards.data>
            @slot('title')
                {{ __('app.budgetvsactualvspoDevT') }}
                <button onclick="downloadChart('totalBudgetChart5')" class="btn btn-sm btn-light" style="float:right; padding:0 5px;" title="Download Chart">
                    <i class="fa fa-download"></i>
                </button>
            @endslot
            <canvas id="totalBudgetChart5" height="500"></canvas>
        </x-cards.data>
    </div>
</div>


    <hr>


</div>
</div>
<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Chart.js datalabels plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
    const labels = @json($totalWBSGraph['labels']);
    const budgetData = @json($totalWBSGraph['values'][0]);
    const poData = @json($totalWBSGraph['values'][1]);
    const actualData = @json($totalWBSGraph['values'][2]);

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
                    anchor: 'center',
                    align: 'center',
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 9
                    },
                    formatter: (value, context) => {
                        // Only show if value is >= 5% of total stack
                        const total = context.chart.data.datasets.reduce((sum, dataset) => sum + dataset.data[context.dataIndex], 0);
                        return value / total >= 0.05 ? value.toFixed(2) : '';
                    }
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
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Amount in Crores (₹)'
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
</script>


<script>
    const totalBudget = @json($totalGraph['values'][0][0]);  
    const totalAmountPlusPoTax = @json($totalGraph['values'][1][0]);  
    const actualTotalPlusActualTax = @json($totalGraph['values'][2][0]);   
    const totalBudgetLabels = @json($totalGraph['labels']); 
    const scaleToCrores = 1; 

    // Convert values to Crores
    const totalBudgetInCrores = totalBudget / scaleToCrores;
    const totalAmountPlusPoTaxInCrores = totalAmountPlusPoTax / scaleToCrores;
    const actualTotalPlusActualTaxInCrores = actualTotalPlusActualTax / scaleToCrores;


    Chart.register(ChartDataLabels);

    const ctx1 = document.getElementById('totalBudgetChart').getContext('2d');

    const totalBudgetChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: totalBudgetLabels, // Use the renamed labels
            datasets: [{
                label: 'Budget vs Actual Comparison',
                data: [
                    totalBudgetInCrores,
                    totalAmountPlusPoTaxInCrores,
                    actualTotalPlusActualTaxInCrores
                ],
                backgroundColor: ['#007bff', '#ffc107', '#28a745'], // Colors for the bars
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
                            // If value is not 0, format it as Crores
                            if (value !== 0) {
                                return '₹ ' + value.toFixed(0); // Show Crores
                            }
                            return '₹ 0'; // Handle zero values gracefully
                        }
                    },
                    grid: {
                        display: false // Remove grid lines
                    }
                }
            },
            plugins: {
                legend: {
                    display: false  
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw;
                            return datasetLabel + ': ₹ ' + value.toFixed(2) + ' Cr'; // Format as Crores in tooltip
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 10
                    },
                    rotation: 360, // Rotate labels to be vertical
                    formatter: (value) => '₹ ' + value.toFixed(2) + ' Cr',
                    offset: 10, // Space between label and bar
                    padding: 4 // Padding for labels
                }
            }
        },
        plugins: [ChartDataLabels] // Enable the data labels plugin
    });
</script>



<script>
    const labels2 = @json($graphBuild['labels']);
    const budgetData2 = @json($graphBuild['values'][0]);
    const poData2 = @json($graphBuild['values'][1]);
    const actualData2 = @json($graphBuild['values'][2]);

    Chart.register(ChartDataLabels);

    const ctx2 = document.getElementById('budgetChart2').getContext('2d');

    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: labels2,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData2,
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'PO',
                    data: poData2,
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Actual',
                    data: actualData2,
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
                    anchor: 'center',
                    align: 'center',
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 9
                    },
                    formatter: (value, context) => {
                        const total = context.chart.data.datasets.reduce(
                            (sum, dataset) => sum + dataset.data[context.dataIndex], 0
                        );
                        return value / total >= 0.05 ? value.toFixed(2) : '';
                    }
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
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Amount in Crores (₹)'
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
</script>


<script>
    const totalBudget1 = @json($graphBuildTotal['values'][0][0]);  // Access the first value for Budget
    const totalAmountPlusPoTax1 = @json($graphBuildTotal['values'][1][0]);  // Access the first value for Committed (PO)
    const actualTotalPlusActualTax1 = @json($graphBuildTotal['values'][2][0]);  // Access the first value for Actual
    const totalBudgetLabels1 = @json($graphBuildTotal['labels']);   
    const scaleToCrores1 = 1;  

    // Convert values to Crores
    const totalBudgetInCrores1 = totalBudget1 / scaleToCrores1;
    const totalAmountPlusPoTaxInCrores1 = totalAmountPlusPoTax1 / scaleToCrores1;
    const actualTotalPlusActualTaxInCrores1 = actualTotalPlusActualTax1 / scaleToCrores1;


    Chart.register(ChartDataLabels);

    const ctx3 = document.getElementById('totalBudgetChart3').getContext('2d');

    const totalBudgetChart3 = new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: totalBudgetLabels, // Use the renamed labels
            datasets: [{
                label: 'Budget vs Actual Comparison',
                data: [
                    totalBudgetInCrores1,
                    totalAmountPlusPoTaxInCrores1,
                    actualTotalPlusActualTaxInCrores1
                ],
                backgroundColor: ['#007bff', '#ffc107', '#28a745'], // Colors for the bars
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
                            // If value is not 0, format it as Crores
                            if (value !== 0) {
                                return '₹ ' + value.toFixed(0) ; // Show Crores
                            }
                            return '₹ 0'; // Handle zero values gracefully
                        }
                    },
                    grid: {
                        display: false // Remove grid lines
                    }
                }
            },
            plugins: {
                legend: {
                    display: false  
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw;
                            return datasetLabel + ': ₹ ' + value.toFixed(2) + ' Cr'; // Format as Crores in tooltip
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 10
                    },
                    rotation: 360, // Rotate labels to be vertical
                    formatter: (value) => '₹ ' + value.toFixed(2),
                    offset: 10, // Space between label and bar
                    padding: 4 // Padding for labels
                }
            }
        },
        plugins: [ChartDataLabels] // Enable the data labels plugin
    });
</script>

<script>
    const labels4 = @json($graphDevTech['labels']);
    const budgetData4 = @json($graphDevTech['values'][0]);
    const poData4 = @json($graphDevTech['values'][1]);
    const actualData4 = @json($graphDevTech['values'][2]);

    Chart.register(ChartDataLabels);

    const ctx4 = document.getElementById('budgetChart4').getContext('2d');

    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: labels4,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData4,
                    backgroundColor: '#007bff',
                    borderColor: '#007bff',
                    borderWidth: 1
                },
                {
                    label: 'PO',
                    data: poData4,
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                },
                {
                    label: 'Actual',
                    data: actualData4,
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
                        size: 10
                    },
                    rotation: 360, // Rotate labels to be vertical
                    formatter: (value) => '₹ ' + value.toFixed(2),
                    offset: 10, // Space between label and bar
                    padding: 4 // Padding for labels
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
</script>

<script>
    const totalBudget5 = @json($graphDevTechTotal['values'][0][0]);  
    const totalAmountPlusPoTax5 = @json($graphDevTechTotal['values'][1][0]);   
    const actualTotalPlusActualTax5 = @json($graphDevTechTotal['values'][2][0]);  // Access the first value for Actual
    const totalBudgetLabels5 = @json($graphDevTechTotal['labels']);   
    const scaleToCrores5 = 1;  
    const totalBudgetInCrores5 = totalBudget5 / scaleToCrores5;
    const totalAmountPlusPoTaxInCrores5 = totalAmountPlusPoTax5 / scaleToCrores5;
    const actualTotalPlusActualTaxInCrores5 = actualTotalPlusActualTax5 / scaleToCrores5;


    Chart.register(ChartDataLabels);

    const ctx5 = document.getElementById('totalBudgetChart5').getContext('2d');

    const totalBudgetChart5 = new Chart(ctx5, {
        type: 'bar',
        data: {
            labels: totalBudgetLabels5, // Use the renamed labels
            datasets: [{
                label: 'Budget vs Actual Comparison',
                data: [
                    totalBudgetInCrores5,
                    totalAmountPlusPoTaxInCrores5,
                    actualTotalPlusActualTaxInCrores5
                ],
                backgroundColor: ['#007bff', '#ffc107', '#28a745'], // Colors for the bars
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
                            // If value is not 0, format it as Crores
                            if (value !== 0) {
                                return '₹ ' + value.toFixed(0); // Show Crores
                            }
                            return '₹ 0'; // Handle zero values gracefully
                        }
                    },
                    grid: {
                        display: false // Remove grid lines
                    }
                }
            },
            plugins: {
                legend: {
                    display: false  
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw;
                            return datasetLabel + ': ₹ ' + value.toFixed(2) + ' Cr'; // Format as Crores in tooltip
                        }
                    }
                },
                 datalabels: {
                    anchor: 'end',
                    align: 'top',
                    font: {
                        size: 10
                    },
                    rotation: 360, // Rotate labels to be vertical
                    formatter: (value) => '₹ ' + value.toFixed(2),
                    offset: 10, // Space between label and bar
                    padding: 4 // Padding for labels
                },
            }
        },
        plugins: [ChartDataLabels] // Enable the data labels plugin
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




<script>
    $(document).ready(function() {
        $('.change-status').change(function() {
            var status = $(this).val();
            var url = "{{ route('projects.update_status', $project->id) }}";
            var token = '{{ csrf_token() }}'

            $.easyAjax({
                url: url,
                type: "POST",
                container: '.content-wrapper',
                blockUI: true,
                data: {
                    status: status,
                    _token: token
                }
            });
        });

        $('body').on('click', '#pinnedItem', function() {
            var type = $('#pinnedItem').attr('data-pinned');
            var id = '{{ $project->id }}';
            var pinType = 'project';

            var dataPin = type.trim(type);
            if (dataPin == 'pinned') {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmUnpin')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('projects.destroy_pin', ':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";
                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                'type': pinType
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    window.location.reload();
                                }
                            }
                        })
                    }
                });

            } else {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmPin')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('projects.store_pin') }}?type=" + pinType;

                        var token = "{{ csrf_token() }}";
                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {
                                '_token': token,
                                'project_id': id
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    window.location.reload();
                                }
                            }
                        });
                    }
                });
            }
        });

        $('body').on('click', '.restore-project', function() {
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.unArchiveMessage')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmRevert')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('projects.archive_restore', $project->id) }}";

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                window.location.reload();
                            }
                        }
                    }); 
                }
            });
        });

        $('body').on('click', '#new-chat', function() {
            let clientId = $(this).data('client-id');
            const url = "{{ route('messages.create') }}?clientId=" + clientId;
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

    });
</script>

