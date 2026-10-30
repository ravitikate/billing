<?php
namespace App\Traits;

use App\Models\Attendance;
use App\Models\DashboardWidget;
use App\Models\TaskboardColumn;
use App\Services\DashboardDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait PurchaseDashboard
{
    public function purchaseDashboard()
    {
        $user                = user();
        $completedTaskColumn = TaskboardColumn::completeColumn();

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
                now(company()->timezone)->lessThan($officeEndTime)
                || (now(company()->timezone)->greaterThan($officeEndTime) && now(company()->timezone)->lessThan($officeStartTime))
            ) {
                $officeStartTime->subDay();
            } else {
                $officeEndTime->addDay();
            }
        }

        $this->cannotLogin = false;
        $Utc               = now(company()->timezone)->format('p');

        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
            ->get();

        foreach ($attendance as $item) {
            if ($item->clock_out_time !== null && now()->between($item->clock_in_time, $item->clock_out_time)) {
                $this->cannotLogin = true;
                break;
            }
        }

        $this->checkJoiningDate = ! (is_null(user()->employeeDetail->joining_date) || user()->employeeDetail->joining_date->gt(now()));

        $this->viewEventPermission    = user()->permission('view_events');
        $this->viewHolidayPermission  = user()->permission('view_holiday');
        $this->viewTaskPermission     = user()->permission('view_tasks');
        $this->viewTicketsPermission  = user()->permission('view_tickets');
        $this->viewLeavePermission    = user()->permission('view_leave');
        $this->viewNoticePermission   = user()->permission('view_notice');
        $this->editTimelogPermission  = user()->permission('edit_timelogs');
        $this->instantDebitPermission = user()->permission('instant_debit');

        $this->event_filter  = explode(',', user()->employeeDetail->calendar_view);
        $this->widgets       = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(fn($value) => $value->status == '1')->pluck('widget_name')->toArray();

        // Dashboard stats
        $dashboardService = new DashboardDataService();
        $counts           = $dashboardService->getPurchasePOcount();

        $this->totalProjects    = $counts['totalProjects'];
        $this->totalSubProjects = $counts['totalSubProjects'];
        $this->totalWO          = $counts['totalWO'];
        $this->totalValidateWO  = $counts['totalValidateWO'];
        $this->data['poData']   = $dashboardService->getMonthlyPurchasePOVsValidatePOAmt();

        return view('dashboard.purchase.index', $this->data);
    }
}
