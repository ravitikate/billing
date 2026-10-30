<?php
namespace App\Traits;

// use App\Models\Attendance;
// use App\Models\AttendanceSetting;
// use App\Models\Company;
use App\Models\DashboardWidget;
// use App\Models\EmployeeShift;
// use App\Models\Notice;
// use App\Models\Project;
// use App\Models\Task;
// use App\Models\TaskboardColumn;
// use App\Models\Ticket;
// use App\Models\TicketAgentGroups;
// use App\Models\User;
// use Carbon\Carbon;
// use Illuminate\Contracts\View\View;
// use Illuminate\Support\Facades\DB;
use App\Services\LevelDashboardDataService;

/**
 *
 */
trait BillDashboard
{

    /**
     */
    public function billDashboard()
    {
        $this->viewEventPermission    = user()->permission('view_events');
        $this->viewHolidayPermission  = user()->permission('view_holiday');
        $this->viewTaskPermission     = user()->permission('view_tasks');
        $this->viewTicketsPermission  = user()->permission('view_tickets');
        $this->viewLeavePermission    = user()->permission('view_leave');
        $this->viewNoticePermission   = user()->permission('view_notice');
        $this->editTimelogPermission  = user()->permission('edit_timelogs');
        $this->instantDebitPermission = user()->permission('instant_debit');
        $this->event_filter           = explode(',', user()->employeeDetail->calendar_view);
        $this->widgets                = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets          = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $LevelDashboardDataService = new LevelDashboardDataService();
        $this->paidCount           = $LevelDashboardDataService->allCount()['paid'];
        $this->dueCount            = $LevelDashboardDataService->allCount()['due'];
        $this->tatCount            = $LevelDashboardDataService->allCount()['tat'];
        $this->overShotCount       = $LevelDashboardDataService->allCount()['overShotCount'];

        $this->paid     = $LevelDashboardDataService->getBillsByStatus('paid');
        $this->dueBills = $LevelDashboardDataService->getBillsByStatus('due');
        $this->tat      = $LevelDashboardDataService->getBillsByStatus('open', 'within');
        $this->overShot = $LevelDashboardDataService->getBillsByStatus('open', 'overshot');

        $paidvsduechart = (new LevelDashboardDataService)->generateMonthlyBillStats();

        $chartLabels        = [];
        $chartTotalAmounts  = [];
        $chartPaidAmounts   = [];
        $chartTotalCombined = [];

        foreach ($paidvsduechart as $item) {
            $chartLabels[]        = $item['y'];
            $chartTotalAmounts[]  = round($item['totalAmount'] / 100000, 2);
            $chartPaidAmounts[]   = round($item['paidAmount'] / 100000, 2);
            $chartTotalCombined[] = round(($item['totalAmount'] + $item['paidAmount']) / 100000, 2);
        }

        $this->data['chartLabels']        = $chartLabels;
        $this->data['chartTotalAmounts']  = $chartTotalAmounts;
        $this->data['chartPaidAmounts']   = $chartPaidAmounts;
        $this->data['chartTotalCombined'] = $chartTotalCombined;
        return view('dashboard.bill.index', $this->data);
    }
}
