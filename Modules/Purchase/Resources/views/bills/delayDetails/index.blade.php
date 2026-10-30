@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <style>
            .delay-details-card {
                border-radius: 12px;
                box-shadow: 0 4px 18px rgba(20, 33, 61, .07);
            }

            .delay-details-table thead th {
                background: #f5f7fb;
                border-top: 0;
                white-space: nowrap;
            }

            .delay-details-table td {
                vertical-align: middle;
            }

            .delay-pagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                padding: 12px 16px;
                border-top: 1px solid #edf0f5;
            }

            .delay-pagination .pagination {
                margin: 0;
                flex-wrap: wrap;
            }

            .delay-pagination .page-link {
                border-radius: 6px;
                margin: 0 2px;
            }

            @media (max-width: 575.98px) {
                .delay-pagination {
                    justify-content: center;
                    text-align: center;
                }

                .delay-pagination nav {
                    width: 100%;
                }

                .delay-pagination .pagination {
                    justify-content: center;
                }
            }
        </style>

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card border-0 b-shadow-4 p-3 mb-3">
            <form method="GET" action="{{ route('bills.delay-details') }}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label for="employee_id">Employee</label>
                        <select name="employee_id" id="employee_id" class="form-control select-picker" data-live-search="true">
                            <option value="">All employees</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="bill_number">Bill number</label>
                        <input type="text" name="bill_number" id="bill_number" class="form-control"
                            value="{{ $filters['bill_number'] ?? '' }}" placeholder="Bill number">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="project_id">@lang('app.project')</label>
                        <select name="project_id" id="project_id" class="form-control select-picker" data-live-search="true">
                            <option value="">All projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="subproject_id">@lang('app.subproject')</label>
                        <select name="subproject_id" id="subproject_id" class="form-control select-picker" data-live-search="true">
                            <option value="">All subprojects</option>
                            @foreach ($subprojects as $subproject)
                                <option value="{{ $subproject->id }}" @selected(($filters['subproject_id'] ?? '') == $subproject->id)>
                                    {{ $subproject->heading }} ({{ $subproject->project_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end align-items-center">
                        <a href="{{ route('bills.delay-details') }}" class="btn btn-light border mr-2">Clear filters</a>
                        <a href="{{ route('bills.delay-details.export', request()->query()) }}" class="btn btn-success">
                            <i class="fa fa-file-excel-o mr-1" aria-hidden="true"></i> Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="d-flex flex-column w-tables rounded bg-white delay-details-card">
            <div class="table-responsive">
            <table class="table table-hover border-0 delay-details-table mb-0">
                <thead>
                    <tr>
                        <th>Bill number</th>
                        <th>@lang('app.project')</th>
                        <th>@lang('app.subproject')</th>
                        <th>@lang('app.bill_date')</th>
                        <th>@lang('app.status')</th>
                        <th>{{ !empty($filters['employee_id']) ? 'Employee delay (days)' : 'Cumulative delay (days)' }}</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $bill->purchase_bill_number }}</td>
                            <td>{{ $bill->project_name }}</td>
                            <td>{{ $bill->subproject_name }}</td>
                            <td>{{ $bill->bill_date ? \Carbon\Carbon::parse($bill->bill_date)->format('d-M-Y') : '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</td>
                            <td class="{{ $bill->total_delay_days > 0 ? 'text-danger font-weight-bold' : 'font-weight-bold' }}">
                                {{ $bill->total_delay_days }} days
                            </td>
                            <td class="text-center">
                                <a href="{{ route('bills.delay-details.show', $bill->bill_id) }}"
                                    class="btn btn-outline-primary btn-sm" title="View bill delay details"
                                    aria-label="View bill delay details">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">@lang('messages.noRecordFound')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="delay-pagination">
                <small class="text-muted">
                    Showing {{ $bills->firstItem() ?? 0 }}–{{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} bills
                </small>
                {{ $bills->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.querySelector('.content-wrapper form[action="{{ route('bills.delay-details') }}"]');
            if (!form) return;

            form.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    form.submit();
                });
            });

            const billNumber = form.querySelector('#bill_number');
            let debounce;
            billNumber.addEventListener('input', function () {
                clearTimeout(debounce);
                debounce = setTimeout(function () {
                    form.submit();
                }, 450);
            });
        })();
    </script>
@endpush