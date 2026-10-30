<?php
namespace App\Traits;

use App\Models\Attendance;
use App\Models\DashboardWidget;
use App\Models\TaskboardColumn;
use App\Services\LevelDashboardDataService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

trait ContractDashboard
{
    public function contractDashboard()
    {
        $user          = user();
        $this->user    = $user;
        $this->company = company();

        $completedTaskColumn      = TaskboardColumn::completeColumn();
        $showClockIn              = attendance_setting();
        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
        $endTimestamp   = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;

        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime   = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        if (! is_null($this->attendanceSettings->early_clock_in)) {
            $officeStartTime->subMinutes($this->attendanceSettings->early_clock_in);
        }

        if ($officeStartTime->gt($officeEndTime)) {
            if (
                now($this->company->timezone)->lessThan($officeEndTime) ||
                (now($this->company->timezone)->greaterThan($officeEndTime) && now($this->company->timezone)->lessThan($officeStartTime))
            ) {
                $officeStartTime->subDay();
            } else {
                $officeEndTime->addDay();
            }
        }

        $this->cannotLogin = false;

        $Utc        = now($this->company->timezone)->format('p');
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
            ->get();

        foreach ($attendance as $item) {
            if ($item->clock_out_time !== null && now()->between($item->clock_in_time, $item->clock_out_time)) {
                $this->cannotLogin = true;
                break;
            }
        }

        $this->checkJoiningDate = ! is_null($user->employeeDetail->joining_date) &&
        ! $user->employeeDetail->joining_date->gt(now());

        $this->viewEventPermission    = $user->permission('view_events');
        $this->viewHolidayPermission  = $user->permission('view_holiday');
        $this->viewTaskPermission     = $user->permission('view_tasks');
        $this->viewTicketsPermission  = $user->permission('view_tickets');
        $this->viewLeavePermission    = $user->permission('view_leave');
        $this->viewNoticePermission   = $user->permission('view_notice');
        $this->editTimelogPermission  = $user->permission('edit_timelogs');
        $this->instantDebitPermission = $user->permission('instant_debit');

        $LevelDashboardDataService = new LevelDashboardDataService();
        $this->totalProjects       = $LevelDashboardDataService->allCount()['totalProjects'];

        $this->totalSubProjects = $LevelDashboardDataService->allCount()['totalSubProjects'];

        $this->totalWO         = $LevelDashboardDataService->allCount()['totalWO'];
        $this->totalValidateWO = $LevelDashboardDataService->allCount()['totalValidateWO'];
        // $this->totalProjects    = DB::table('projects')->count();
        // $this->totalSubProjects = DB::table('tasks')->count();
        // $this->totalWO          = DB::table('purchase_orders')->where('order_type', 'work_order')->count();
        // $this->totalValidateWO  = DB::table('purchase_orders')->where('order_type', 'work_order')->where('validate', true)->count();

        $this->event_filter  = explode(',', $user->employeeDetail->calendar_view);
        $this->widgets       = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->where('status', '1')->pluck('widget_name')->toArray();

        $this->data['poData']          = $this->contractSignORNot();
        $this->data['contractSummary'] = $this->contractCount();

        return view('dashboard.contract.index', $this->data);
    }

    public function contractSignORNot()
    {
        $CRORE_DIVISOR = 10000000;
        $userId        = $this->user->id;

        $period = CarbonPeriod::create(now()->subMonths(11)->startOfMonth(), '1 month', now());
        $months = [];

        foreach ($period as $date) {
            $key          = $date->format('Y-m');
            $months[$key] = [
                'label'     => $date->translatedFormat('F'),
                'total'     => 0,
                'validated' => 0,
            ];
        }

        $totalAmounts = DB::table('purchase_orders as po')
            ->join('purchase_items as pi', 'pi.purchase_order_id', '=', 'po.id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->where('po.order_type', 'work_order')
            ->where('pm.user_id', $userId)
            ->whereBetween('po.created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(po.created_at, '%Y-%m') as month, SUM(pi.amount + pi.gst_amount) as total_amount")
            ->groupBy('month')
            ->get();

        $validatedAmounts = DB::table('purchase_orders as po')
            ->join('purchase_items as pi', 'pi.purchase_order_id', '=', 'po.id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->where('po.order_type', 'work_order')
            ->where('po.validate', true)
            ->where('pm.user_id', $userId)
            ->whereBetween('po.updated_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(po.updated_at, '%Y-%m') as month, SUM(pi.amount + pi.gst_amount) as validated_amount")
            ->groupBy('month')
            ->get();

        foreach ($totalAmounts as $row) {
            if (isset($months[$row->month])) {
                $months[$row->month]['total'] = round($row->total_amount);
            }
        }

        foreach ($validatedAmounts as $row) {
            if (isset($months[$row->month])) {
                $months[$row->month]['validated'] = round($row->validated_amount);
            }
        }

        $graphData = [];

        foreach ($months as $month) {
            $graphData[] = [
                'y'         => $month['label'],
                'committed' => round($month['total'] / $CRORE_DIVISOR, 2),
                'actual'    => round($month['validated'] / $CRORE_DIVISOR, 2),
            ];
        }

        $graphData = collect($graphData);

        return [
            'labels' => $graphData->pluck('y'),
            'values' => [
                $graphData->pluck('committed'),
                $graphData->pluck('actual'),
            ],
            'colors' => ['#6f42c1', '#28a745'],
            'name'   => ['Total Amount (Cr)', 'Validated Amount (Cr)'],
        ];
    }

    public function contractCount()
    {
        $totals = DB::table('contracts as c')
            ->leftJoin('contract_signs as cs', 'cs.contract_id', '=', 'c.id')
            ->selectRaw("
                SUM(CASE WHEN cs.contract_id IS NOT NULL THEN c.amount ELSE 0 END) AS total_signed_amount,
                SUM(CASE WHEN cs.contract_id IS NULL THEN c.amount ELSE 0 END) AS total_unsigned_amount
            ")
            ->first();

        $signedAmount   = round($totals->total_signed_amount ?? 0, 2);
        $unsignedAmount = round($totals->total_unsigned_amount ?? 0, 2);

        $labels = ['Total Sign Amount', 'Total Unsigned Amount'];

        $colors = collect($labels)->map(function ($label) {
            return '#' . substr(md5($label), 0, 6);
        });

        return [
            'labels' => $labels,
            'values' => [$signedAmount, $unsignedAmount],
            'colors' => $colors,
        ];
    }
}
