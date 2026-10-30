@extends('layouts.app')

@push('styles')
    <script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
    <script src="{{ asset('vendor/jquery/Chart.min.js') }}"></script>
@endpush

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="px-4 py-0 py-lg-4  border-top-0 admin-dashboard">
        <div class="row">
            @if (in_array('projects', user_modules()))

                <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div
                        class="bg-white p-3 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                        <div class="d-block ">
                            <h5 class="f-13 f-w-500 mb-20 text-darkest-grey">@lang('modules.dashboard.totalProjects')</h5>
                            <div class="d-flex">
                                <a href="{{ route('projects.index') }}?status=all">
                                    <p class="mb-0 f-15 font-weight-bold text-blue d-grid mr-5">
                                        {{ $counts->totalProjects }}<span class="f-12 font-weight-normal text-lightest">
                                            @lang('modules.dashboard.assignProject') </span>
                                    </p>
                                </a>
                            </div>
                        </div>
                        <div class="d-block">
                            <i class="fa fa-file-invoice text-lightest f-18"></i>
                        </div>
                    </div>
                </div>
            @endif

            @if (in_array('contracts', user_modules()))

                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div
                        class="bg-white p-3 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                        <div class="d-block ">
                            <h5 class="f-13 f-w-500 mb-20 text-darkest-grey">@lang('modules.dashboard.totalContractsSigned')</h5>
                            <div class="d-flex">
                                <a href="{{ route('contracts.index') }}?signed=yes">
                                    <p class="mb-0 f-15 font-weight-bold text-blue d-grid mr-5">
                                        {{ $totalContractsSigned }}<span class="f-12 font-weight-normal text-lightest">
                                            @lang('modules.dashboard.contractsSigned') </span>
                                    </p>
                                </a>

                            </div>
                        </div>
                        <div class="d-block">
                            <i class="file-signature"></i>
                        </div>
                    </div>
                </div>
            @endif

            @if (in_array('invoices', user_modules()))
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div
                        class="bg-white p-3 rounded b-shadow-4 d-flex justify-content-between align-items-center mb-4 mb-md-0 mb-lg-0">
                        <div class="d-block ">
                            <h5 class="f-13 f-w-500 mb-20 text-darkest-grey">@lang('app.menu.invoices')</h5>
                            <div class="d-flex">
                                <a href="{{ route('invoices.index') . '?status=paid' }}">
                                    <p class="mb-0 f-15 font-weight-bold text-blue d-grid mr-5">
                                        {{ $totalPaidInvoice }}<span class="f-12 font-weight-normal text-lightest">
                                            @lang('modules.dashboard.totalPaidInvoices') </span>
                                    </p>
                                </a>

                                <a href="{{ route('invoices.index') . '?status=pending' }}">
                                    <p class="mb-0 f-15 font-weight-bold text-red d-grid">
                                        {{ $totalUnPaidInvoice }}<span
                                            class="f-12 font-weight-normal text-lightest">@lang('modules.dashboard.totalUnpaidInvoices')</span>
                                    </p>
                                </a>
                            </div>
                        </div>
                        <div class="d-block">
                            <i class="fa fa-file-invoice text-lightest f-18"></i>
                        </div>
                    </div>
                </div>
            @endif


        </div>

        <div class="row">
            <div class="col-sm-12 col-lg-6 mt-4" >
                <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                    <x-cards.data :title="__('modules.tasks.invoiceDetails')" padding="false" otherClasses="h-200">
                        <x-table>
                            <x-slot name="thead">
                                <th>@lang('app.invoiceNo')#</th>
                                <th>@lang('app.contractNo')#</th>
                                <th class="text-center">@lang('app.contractorName')</th>
                                <th class="text-right pr-20">@lang('app.dueDate')</th>
                                <th class="text-right pr-20">@lang('app.status')</th>
                            </x-slot>

                            @forelse ($invoices as $task)
                                <tr>
                                    <td class="pl-20">
                                        <a  href="javascript:;" data-invoice-id="{{ $task->invoiceId }}"
                                            class="redirectInvoice openRightModal f-12 mb-1 text-darkest-grey">
                                             {{ $task->invoice_number }}
                                         </a>
                                    </td>
                                    {{-- <td class="pl-20">
                                        <a
                                            href="#"
                                            class="redirectContract f-12 mb-1 text-darkest-grey">#{{  }}</a>

                                    </td> --}}
                                    <td class="pr-20" align="center">
                                        {{ $task->contract_number }}
                                    </td>
                                    <td class="pr-20" align="right">
                                        {{ $task->subject }}
                                    </td>

                                    <td class="pr-20" align="right"
                                        style="color: {{ (\Carbon\Carbon::parse($task->due_date)->isPast() && $task->status == 'unpaid') ? 'red' : 'blue' }};">
                                        {{ $task->due_date }}
                                    </td>

                                    <td class="pr-20" align="right"
                                        style="color: {{ $task->status == 'paid' ? 'blue' : 'red' }};">
                                        {{ ucfirst($task->status) }}
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


            @if (in_array('projects', user_modules()))
                <div class="col-sm-12 col-lg-6 mt-4">
                    <x-cards.data :title="__('modules.dashboard.statusWiseProject')">
                        @if (array_sum($statusWiseProject['values']) > 0)
                            <a href="javascript:;" class="text-darkest-grey f-w-500 piechart-full-screen" data-chart-id="status-wise-project" data-chart-data="{{ json_encode($statusWiseProject) }}"><i class="fas fa-expand float-right mr-3"></i></a>
                        @endif
                        <x-pie-chart id="task-chart" :labels="$statusWiseProject['labels']"
                                     :values="$statusWiseProject['values']" :colors="$statusWiseProject['colors']"
                                     height="300"
                                     width="300"/>
                    </x-cards.data>
                </div>
            @endif

            @if (in_array('projects', user_modules()) && $viewMilestonePermission != 'none')
                <div class="col-sm-12 col-lg-6 mt-4">
                    <x-cards.data :title="__('modules.dashboard.pendingMilestone')" padding="false"
                                  otherClasses="h-200">
                        <div class="table-responsive">
                            <x-table class="border-0 pb-3 admin-dash-table table-hover">

                                <x-slot name="thead">
                                    <th class="pl-20">#</th>
                                    <th>@lang('modules.projects.milestoneTitle')</th>
                                    <th>@lang('modules.projects.milestoneCost')</th>
                                    <th>@lang('app.project')</th>
                                </x-slot>

                                @forelse($pendingMilestone as $key=>$item)
                                    <tr id="row-{{ $item->id }}">
                                        <td class="pl-20">{{ $key + 1 }}</td>
                                        <td>
                                            <a href="javascript:;" class="milestone-detail text-darkest-grey f-w-500"
                                               data-milestone-id="{{ $item->id }}">{{ $item->milestone_title }}</a>
                                        </td>
                                        <td>
                                            @if (!is_null($item->currency_id))
                                                {{ $item->currency->currency_symbol . $item->cost }}
                                            @else
                                                {{ $item->cost }}
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('projects.show', [$item->project_id]) }}"
                                               class="text-darkest-grey">{{ $item->project->project_name }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <x-cards.no-record icon="list" :message="__('messages.noRecordFound')"/>
                                        </td>
                                    </tr>
                                @endforelse
                            </x-table>
                        </div>
                    </x-cards.data>
                </div>
            @endif

        </div>

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    <script>
        $('body').on('click', '.milestone-detail', function () {
            const id = $(this).data('milestone-id');
            let url = "{{ route('milestones.show', ':id') }}";
            url = url.replace(':id', id);
            $(MODAL_XL + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        $(document).on('click', '.redirectInvoice', function() {
            var invoiceId = $(this).data('invoice-id'); // Get invoice ID from data attribute
            var url = `{{ route('invoices.show', ['invoice' => ':invoiceId']) }}`.replace(':invoiceId', invoiceId);
            window.location.href = url;
        });
        // $(document).on('click', '.redirectInvoice', function() {
        //     var invoiceId = $(this).data('invoice-id'); // Get invoice ID from data attribute
        //     var url = `{{ route('invoices.show', ['invoice' => ':invoiceId']) }}`.replace(':invoiceId', invoiceId);
        //     window.location.href = url;
        // });
    </script>

@endpush
