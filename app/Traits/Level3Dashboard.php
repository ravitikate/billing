<?php
namespace App\Traits;

use App\Models\DashboardWidget;
use App\Services\LevelDashboardDataService;

/**
 *
 */
trait Level3Dashboard
{

    /**
     */
    public function level3Dashboard()
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

        $this->abstractCount    = $LevelDashboardDataService->allCount()['abstract'];
        $this->pendingCount     = $LevelDashboardDataService->allCount()['pending'];
        $this->paidCount        = $LevelDashboardDataService->allCount()['paid'];
        $this->dueCount         = $LevelDashboardDataService->allCount()['due'];
        $this->tatCount         = $LevelDashboardDataService->allCount()['tat'];
        $this->overShotCount    = $LevelDashboardDataService->allCount()['overShotCount'];
        $this->totalProjects    = $LevelDashboardDataService->allCount()['totalProjects'];
        $this->totalSubProjects = $LevelDashboardDataService->allCount()['totalSubProjects'];
        $this->totalPO          = $LevelDashboardDataService->allCount()['totalPO'];
        $this->totalValidatePO  = $LevelDashboardDataService->allCount()['totalValidatePO'];
        $this->totalWO          = $LevelDashboardDataService->allCount()['totalWO'];
        $this->totalValidateWO  = $LevelDashboardDataService->allCount()['totalValidateWO'];
        $this->totalBill        = $LevelDashboardDataService->allCount()['totalBill'];

        $this->paid     = $LevelDashboardDataService->getBillsByStatus('paid');
        $this->abstract = $LevelDashboardDataService->getBillsByStatus('abstract');
        $this->pending  = $LevelDashboardDataService->getBillsByStatus('pending');
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

        $this->data['poData'] = (new LevelDashboardDataService)->getMonthlyPurchasePOVsValidatePOAmt();

        $final                       = (new LevelDashboardDataService)->fetchBudgetData();
        $this->data['totalWBSGraph'] = (new LevelDashboardDataService)->totalWBSGraph($final); 
        return view('dashboard.level3.index', $this->data);

    }

}
