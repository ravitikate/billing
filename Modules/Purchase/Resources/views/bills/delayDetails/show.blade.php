@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="card border-0 b-shadow-4 p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h4>Bill {{ $bill->purchase_bill_number }} delay details</h4>
                    <p class="mb-1"><strong>@lang('app.project'):</strong> {{ $bill->project_name }}</p>
                    <p class="mb-1"><strong>@lang('app.subproject'):</strong> {{ $bill->subproject_name }}</p>
                    <p class="mb-1"><strong>@lang('app.bill_date'):</strong>
                        {{ $bill->bill_date ? \Carbon\Carbon::parse($bill->bill_date)->format('d-M-Y') : '—' }}
                    </p>
                    <p class="mb-0"><strong>@lang('app.status'):</strong>
                        {{ ucfirst(str_replace('_', ' ', $bill->status)) }}
                    </p>
                </div>
                <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0">
                    <div class="text-danger font-weight-bold mr-3">
                        Cumulative approver delay: {{ $bill->total_delay_days }} days
                    </div>
                    <a href="{{ route('bills.delay-details.export-show', $bill->id) }}" class="btn btn-success btn-sm">
                        <i class="fa fa-file-excel-o mr-1" aria-hidden="true"></i> Export Excel
                    </a>
                </div>
            </div>
            <small class="text-muted">
                Uses each approver's configured allowance and approval timestamps; stored delay is used when no allowance is configured. This is not the bill's elapsed calendar delay.
            </small>
        </div>

        <div class="d-flex flex-column w-tables rounded bg-white">
            <table class="table table-hover border-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Received bill at</th>
                        <th>Allowed approval time</th>
                        <th>Approval due by</th>
                        <th>Actually approved at</th>
                        <th>Actual time taken</th>
                        <th>Calculated delay</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvals as $approval)
                        <tr>
                            <td>{{ $approval->approver_name }}</td>
                            <td>
                                @if ($approval->received_at)
                                    {{ \Carbon\Carbon::parse($approval->received_at)->format($approval->priority_id === null ? 'd-M-Y' : 'd-M-Y h:i A') }}
                                @else
                                    Not recorded
                                @endif
                            </td>
                            <td>
                                @if ($approval->approval_time !== null && $approval->approval_unit)
                                    {{ $approval->approval_time }} {{ $approval->approval_unit }}
                                @else
                                    Not configured
                                @endif
                            </td>
                            <td>
                                @if ($approval->due_at)
                                    {{ $approval->due_at->format('d-M-Y h:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $approval->approved_at ? \Carbon\Carbon::parse($approval->approved_at)->format('d-M-Y h:i A') : '—' }}</td>
                            <td>
                                @if ($approval->timeline_mismatch)
                                    Timestamp order mismatch
                                @else
                                    {{ $approval->actual_time_taken ?? '—' }}
                                @endif
                            </td>
                            <td class="{{ ($approval->calculated_delay_days ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">
                                {{ $approval->calculated_delay_days ?? 0 }} days
                            </td>
                            <td>
                                @if ($approval->approved_at)
                                    Approved
                                @elseif ($approval->was_bypassed)
                                    Bypassed
                                @elseif ($approval->was_advanced)
                                    Approved
                                @elseif ($approval->received_at)
                                    {{ $approval->timeline_mismatch ? 'Timeline mismatch' : 'Pending' }}
                                @else
                                    Not recorded
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">@lang('messages.noRecordFound')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('bills.delay-details') }}" class="btn btn-secondary mt-3">Back to delay details</a>
    </div>
@endsection