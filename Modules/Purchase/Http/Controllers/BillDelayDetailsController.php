<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\AccountBaseController;
use App\Exports\BillDelayDetailsExport;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class BillDelayDetailsController extends AccountBaseController
{
    public function index(Request $request)
    {
        $bills = $this->filteredBillsQuery($request)
            ->paginate(20)
            ->withQueryString();

        $approvalByRows = DB::table('approval_bills_by')
            ->whereIn('bill_id', $bills->getCollection()->pluck('bill_id'))
            ->get()
            ->keyBy('bill_id');

        $employeeId = $request->input('employee_id');
        $bills->getCollection()->transform(function ($bill) use ($approvalByRows, $employeeId) {
            $approvalBy = $approvalByRows->get($bill->bill_id);
            $approvals = $this->approverTimeline($bill->bill_id, $bill->project_id, $approvalBy);
            $bill->total_delay_days = $this->billDelayTotal($approvals, $employeeId);

            return $bill;
        });
        $bills->setCollection(
            $bills->getCollection()->sortByDesc('total_delay_days')->values()
        );

        $filterQuery = $this->delayedBillsQuery();
        $employees = (clone $filterQuery)
            ->join('users as filter_users', 'filter_users.id', '=', 'pbd.user_id')
            ->select('filter_users.id', 'filter_users.name')
            ->distinct()
            ->orderBy('filter_users.name')
            ->get();

        $projects = (clone $filterQuery)
            ->select('po.project_id as id', 'p.project_name')
            ->distinct()
            ->orderBy('p.project_name')
            ->get();

        $subprojects = (clone $filterQuery)
            ->select('po.subproject_id as id', 't.heading', 'p.project_name')
            ->distinct()
            ->orderBy('t.heading')
            ->get();

        return view('purchase::bills.delayDetails.index', array_merge($this->data, [
            'pageTitle' => 'Delay Details',
            'bills' => $bills,
            'employees' => $employees,
            'projects' => $projects,
            'subprojects' => $subprojects,
            'filters' => $request->only(['employee_id', 'bill_number', 'project_id', 'subproject_id']),
        ]));
    }

    public function exportIndex(Request $request)
    {
        $bills = $this->filteredBillsQuery($request)->get();
        $approvalByRows = DB::table('approval_bills_by')
            ->whereIn('bill_id', $bills->pluck('bill_id'))
            ->get()
            ->keyBy('bill_id');
        $rows = $bills->map(function ($bill) use ($approvalByRows, $request) {
            $approvals = $this->approverTimeline(
                $bill->bill_id,
                $bill->project_id,
                $approvalByRows->get($bill->bill_id)
            );

            return [
                $bill->purchase_bill_number,
                $bill->project_name,
                $bill->subproject_name,
                $bill->bill_date,
                ucfirst(str_replace('_', ' ', $bill->status)),
                $this->billDelayTotal($approvals, $request->input('employee_id')),
            ];
        })->sortByDesc(fn ($row) => $row[5])->values()->all();

        return Excel::download(
            new BillDelayDetailsExport(
                $rows,
                ['Bill number', 'Project', 'Subproject', 'Bill date', 'Status', 'Delay (days)']
            ),
            'bill-delay-details.xlsx'
        );
    }

    public function show(int $billId)
    {
        $bill = $this->delayedBillsQuery()
            ->where('pb.id', $billId)
            ->select(
                'pb.id',
                'pb.purchase_bill_number',
                'pb.bill_date',
                'pb.status',
                'po.project_id',
                'p.project_name',
                'po.subproject_id',
                't.heading as subproject_name'
            )
            ->selectRaw('SUM(pbd.delay_days) as total_delay_days')
            ->groupBy(
                'pb.id',
                'pb.purchase_bill_number',
                'pb.bill_date',
                'pb.status',
                'po.project_id',
                'p.project_name',
                'po.subproject_id',
                't.heading'
            )
            ->first();

        abort_if(! $bill, 404);

        $approvalBy = DB::table('approval_bills_by')
            ->where('bill_id', $billId)
            ->first();

        $approvals = $this->approverTimeline($billId, $bill->project_id, $approvalBy);

        $bill->total_delay_days = $approvals->sum('calculated_delay_days');

        return view('purchase::bills.delayDetails.show', array_merge($this->data, [
            'pageTitle' => 'Bill Delay Details',
            'bill' => $bill,
            'approvals' => $approvals,
        ]));
    }

    public function exportShow(int $billId)
    {
        $bill = $this->delayedBillsQuery()
            ->where('pb.id', $billId)
            ->select('pb.id', 'pb.purchase_bill_number', 'pb.status', 'po.project_id')
            ->first();

        abort_if(! $bill, 404);

        $approvalBy = DB::table('approval_bills_by')->where('bill_id', $billId)->first();
        $approvals = $this->approverTimeline($billId, $bill->project_id, $approvalBy);
        $rows = $approvals->map(fn ($approval) => [
            $approval->approver_name,
            $approval->received_at,
            $approval->approval_time !== null && $approval->approval_unit
                ? $approval->approval_time . ' ' . $approval->approval_unit
                : 'Not configured',
            $approval->due_at?->format('Y-m-d H:i:s'),
            $approval->approved_at,
            $approval->actual_time_taken ?? ($approval->timeline_mismatch ? 'Timestamp order mismatch' : ''),
            $approval->calculated_delay_days,
            $approval->was_bypassed
                ? 'Bypassed'
                : ($approval->approved_at
                    ? 'Approved'
                    : ($approval->was_advanced
                        ? 'Approved'
                        : ($approval->received_at ? 'Pending' : 'Not recorded'))),
        ])->all();

        return Excel::download(
            new BillDelayDetailsExport(
                $rows,
                ['Employee', 'Received bill at', 'Allowed approval time', 'Approval due by', 'Actually approved at', 'Actual time taken', 'Delay (days)', 'Status'],
                'Bill Delay'
            ),
            'bill-' . $billId . '-delay-details.xlsx'
        );
    }

    private function filteredBillsQuery(Request $request): Builder
    {
        $query = $this->delayedBillsQuery();

        if ($request->filled('employee_id')) {
            $query->where('pbd.user_id', $request->input('employee_id'));
        }

        if ($request->filled('bill_number')) {
            $billNumber = $request->input('bill_number');
            $query->where(function ($billQuery) use ($billNumber) {
                $billQuery->where('pb.purchase_bill_number', 'like', '%' . $billNumber . '%')
                    ->orWhere('pb.id', 'like', '%' . $billNumber . '%');
            });
        }

        if ($request->filled('project_id')) {
            $query->where('po.project_id', $request->input('project_id'));
        }

        if ($request->filled('subproject_id')) {
            $query->where('po.subproject_id', $request->input('subproject_id'));
        }

        return $query
            ->select(
                'pb.id as bill_id',
                'pb.purchase_bill_number',
                'pb.bill_date',
                'pb.status',
                'po.project_id',
                'po.subproject_id',
                'p.project_name',
                't.heading as subproject_name'
            )
            ->selectRaw('SUM(pbd.delay_days) as total_delay_days')
            ->groupBy(
                'pb.id',
                'pb.purchase_bill_number',
                'pb.bill_date',
                'pb.status',
                'po.project_id',
                'po.subproject_id',
                'p.project_name',
                't.heading'
            );
    }

    private function billDelayTotal($approvals, $employeeId = null): int
    {
        if ($employeeId !== null && $employeeId !== '') {
            return (int) $approvals
                ->where('user_id', $employeeId)
                ->sum('calculated_delay_days');
        }

        return (int) $approvals->sum('calculated_delay_days');
    }

    private function approverTimeline(int $billId, int $projectId, $approvalBy)
    {
        $eventsByApprover = DB::table('approval_bill_details')
            ->where('bill_id', $billId)
            ->where(function ($query) {
                $query->where('approval_status', 2)
                    ->orWhere('bypass', 'bypass');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['user_id', 'approval_status', 'bypass', 'created_at'])
            ->groupBy('user_id');

        $approvals = DB::table('priority_role as pr')
            ->join('users as approver', 'approver.id', '=', 'pr.user_id')
            ->leftJoin('purchase_bills_delay as delays', function ($join) use ($billId) {
                $join->on('delays.user_id', '=', 'pr.user_id')
                    ->where('delays.bill_id', '=', $billId);
            })
            ->where('pr.project_id', $projectId)
            ->select(
                'pr.priority_id',
                'pr.user_id',
                'pr.approval_time',
                'pr.approval_unit',
                'delays.bill_date as stage_started_at',
                'delays.delay_date',
                'delays.delay_days',
                'approver.name as approver_name'
            )
            ->orderBy('pr.priority_id')
            ->get();

        $approverIds = $approvals->pluck('user_id')->all();
        $historicalDelays = DB::table('purchase_bills_delay as delays')
            ->join('users as approver', 'approver.id', '=', 'delays.user_id')
            ->where('delays.bill_id', $billId)
            ->where('delays.delay_days', '>', 0)
            ->when($approverIds, fn ($query) => $query->whereNotIn('delays.user_id', $approverIds))
            ->select(
                'delays.user_id',
                'delays.bill_date as stage_started_at',
                'delays.delay_date',
                'delays.delay_days',
                'approver.name as approver_name'
            )
            ->get()
            ->map(fn ($delay) => (object) [
                'priority_id' => null,
                'user_id' => $delay->user_id,
                'approval_time' => null,
                'approval_unit' => null,
                'stage_started_at' => $delay->stage_started_at,
                'delay_date' => $delay->delay_date,
                'delay_days' => $delay->delay_days,
                'approver_name' => $delay->approver_name,
            ]);

        $approvals = $approvals->concat($historicalDelays);
        $activePriority = $approvalBy && (int) $approvalBy->status !== 2
            ? (int) $approvalBy->approval_status
            : null;
        $handoffAt = $approvalBy->created_at ?? null;

        foreach ($approvals as $approval) {
            $completion = $eventsByApprover->get($approval->user_id, collect())->last();
            $approval->was_bypassed = $completion && $completion->bypass === 'bypass';
            $approval->approved_at = $completion && (int) $completion->approval_status === 2
                ? $completion->created_at
                : null;
            $approval->timeline_mismatch = false;
            $approval->was_advanced = false;

            if ($approval->priority_id === null) {
                $approval->received_at = $approval->stage_started_at;
                $approval->due_at = null;
                $approval->actual_time_taken = null;
                $approval->calculated_delay_days = (int) $approval->delay_days;

                continue;
            }

            $isCurrent = $activePriority !== null
                && (int) $approval->priority_id === $activePriority;
            $approval->was_advanced = $activePriority !== null
                && (int) $approval->priority_id < $activePriority
                && ! $completion;
            if ($completion) {
                $approval->received_at = $handoffAt;
            } elseif ($isCurrent) {
                $approval->received_at = $handoffAt
                    && $approvalBy->updated_at
                    && Carbon::parse($handoffAt)->greaterThan(Carbon::parse($approvalBy->updated_at))
                        ? $handoffAt
                        : ($approvalBy->updated_at ?: $handoffAt);
            } elseif ($activePriority !== null && (int) $approval->priority_id < $activePriority) {
                $approval->received_at = $handoffAt;
            } else {
                $approval->received_at = null;
            }

            $approval->due_at = null;
            $unit = strtolower(trim((string) $approval->approval_unit));
            $hasConfiguredAllowance = $approval->approval_time !== null
                && in_array($unit, ['hour', 'hours', 'day', 'days'], true);
            $approval->calculated_delay_days = $hasConfiguredAllowance
                ? 0
                : (int) ($approval->delay_days ?? 0);
            if ($approval->received_at && $approval->approval_time !== null) {
                $dueAt = Carbon::parse($approval->received_at);
                if (in_array($unit, ['hour', 'hours'], true)) {
                    $dueAt->addHours((int) $approval->approval_time);
                    $approval->due_at = $dueAt;
                } elseif (in_array($unit, ['day', 'days'], true)) {
                    $dueAt->addDays((int) $approval->approval_time);
                    $approval->due_at = $dueAt;
                }
            }
            if ($approval->due_at) {
                $approval->calculated_delay_days = 0;
            }

            $endAt = $approval->approved_at
                ? Carbon::parse($approval->approved_at)
                : ($isCurrent ? now() : null);

            if ($approval->received_at && $endAt) {
                $receivedAt = Carbon::parse($approval->received_at);
                if ($approval->approved_at && $endAt->greaterThanOrEqualTo($receivedAt)) {
                    $duration = $receivedAt->diff($endAt);
                    $approval->actual_time_taken = $duration->days . ' days '
                        . $duration->h . ' hours ' . $duration->i . ' minutes';
                } elseif ($approval->approved_at) {
                    $approval->timeline_mismatch = true;
                }
            } else {
                $approval->actual_time_taken = null;
            }

            if ($approval->due_at && $endAt
                && $endAt->toDateString() > $approval->due_at->toDateString()) {
                $approval->calculated_delay_days = (int) $approval->due_at
                    ->copy()
                    ->startOfDay()
                    ->diffInDays($endAt->copy()->startOfDay());
            }

            if ($approval->approved_at || $approval->was_bypassed) {
                $handoffAt = $approval->approved_at ?? $completion->created_at;
            }
        }

        return $approvals;
    }
    
    private function delayedBillsQuery(): Builder
    {
        $query = DB::table('purchase_bills_delay as pbd')
            ->join('purchase_bills as pb', 'pb.id', '=', 'pbd.bill_id')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('projects as p', 'p.id', '=', 'po.project_id')
            ->leftJoin('tasks as t', 't.id', '=', 'po.subproject_id')
            ->where('pbd.delay_days', '>', 0)
            ->where('pb.company_id', company()->id);

        if (user()->hasRole('admin')) {
            return $query;
        }

        if (in_array('vendor', user_roles())) {
            $vendorId = DB::table('purchase_vendors')
                ->where('user_id', user()->id)
                ->value('id');

            return $vendorId
                ? $query->where('pb.purchase_vendor_id', $vendorId)
                : $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($projectMembership) {
            $projectMembership->selectRaw('1')
                ->from('project_members as pm')
                ->whereColumn('pm.project_id', 'po.project_id')
                ->where('pm.user_id', user()->id);
        });
    }
}
