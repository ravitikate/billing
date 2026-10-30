<?php
namespace App\Traits;

use App\Models\DashboardWidget;
use App\Services\LevelDashboardDataService;
use Illuminate\Support\Facades\DB;

/**
 *
 */
trait EmployeeDashboard
{

    /**
     */
    public function employeeDashboard()
    {
        $currentDate = now();
        
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

        $this->abstractCount    = $LevelDashboardDataService->allCount()['abstract'];

        $this->approve_bill = $this->bill_status  =  $this->pendingCount     = $LevelDashboardDataService->allCount()['pending'];
        $this->review_action        = $LevelDashboardDataService->review_action()['pending'];
        $this->paidCount        = $LevelDashboardDataService->allCount()['paid'];
        $this->dueCount         = $LevelDashboardDataService->allCount()['due'];
        $this->delayBillCount   = $LevelDashboardDataService->allCount()['delayBillCount'];
        $this->unpaidBillCount  = $LevelDashboardDataService->allCount()['unpaidBillCount'];
        $this->tatCount         = $LevelDashboardDataService->allCount()['tat'];
        $this->overShotCount    = $LevelDashboardDataService->allCount()['overShotCount'];
        $this->totalProjects    = $LevelDashboardDataService->allCount()['totalProjects'];
        $this->totalSubProjects = $LevelDashboardDataService->allCount()['totalSubProjects'];
        $this->totalPO          = $LevelDashboardDataService->allCount()['totalPO'];
        $this->totalValidatePO  = $LevelDashboardDataService->allCount()['totalValidatePO'];
        $this->totalWO          = $LevelDashboardDataService->allCount()['totalWO'];
        $this->totalValidateWO  = $LevelDashboardDataService->allCount()['totalValidateWO'];
        $this->totalBill        = $LevelDashboardDataService->allCount()['totalBill'];

        $this->paid       = $LevelDashboardDataService->getBillsByStatus('paid');
        $this->abstract   = $LevelDashboardDataService->getBillsByStatus('abstract');
        $this->pending    = $LevelDashboardDataService->getBillsByStatus('pending');
        $this->dueBills   = $LevelDashboardDataService->getBillsByStatus('due');
        $this->delayBills = $LevelDashboardDataService->getBillsByStatus('', 'delay'); // not paid
        $this->tat        = $LevelDashboardDataService->getBillsByStatus('open', 'within');
        $this->overShot   = $LevelDashboardDataService->getBillsByStatus('open', 'overshot');


        $this->intatUnder   = $LevelDashboardDataService->getBillsBySlaStatus('intat');
        $this->overshotUnder   = $LevelDashboardDataService->getBillsBySlaStatus('overshot');
        $this->user_idd = user()->id;

// $this->totalBillApproveByuser = DB::table('approval_bill_details')
//     ->where('user_id',15)
//     ->where('approval_status', 2)
//     ->count();


        $this->intatBillcounts = $LevelDashboardDataService->intatBillcounts();

        $this->billTimeline    = $LevelDashboardDataService->intatAndOvershotBills();
        $this->intatUnderProcessBill = $LevelDashboardDataService->intatUnderProcessBill();


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

        $this->data['poData'] = (new LevelDashboardDataService)->getMonthlyPurchasePOVsValidatePOAmt();

        $final                       = (new LevelDashboardDataService)->fetchBudgetData();
        $this->data['totalWBSGraph'] = (new LevelDashboardDataService)->totalWBSGraph($final);
        $this->data['chartPaidVsUnpaidVsDelay'] =
        (new LevelDashboardDataService)->getPaidVsUnpaidVsDelay()->getData(true);
        // print_r($this->data['chartPaidVsUnpaidVsDelay']);
        return view('dashboard.employee.index', $this->data);

    }
}
