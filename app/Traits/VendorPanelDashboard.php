<?php
namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\DashboardWidget;
use App\Services\LevelDashboardDataService;
/**
 *
 */
trait VendorPanelDashboard
{

    /**
     * @return \Illuminate\Http\Response
     */
    public function VendorPanelDashboard()
    {
        $this->viewEventPermission    = user()->permission('view_events');   
        $this->viewHolidayPermission  = user()->permission('view_holiday');
        $this->viewTaskPermission     = user()->permission('view_tasks');
        $this->viewTicketsPermission  = user()->permission('view_tickets');
        $this->viewLeavePermission    = user()->permission('view_leave');
        $this->viewNoticePermission   = user()->permission('view_notice');
        $this->editTimelogPermission  = user()->permission('edit_timelogs');
        $this->instantDebitPermission = user()->permission('instant_debit'); 
        
        $this->widgets                = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets          = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $LevelDashboardDataService = new LevelDashboardDataService();
        $billCounts = $LevelDashboardDataService->allCount();

        $this->abstractCount    = $billCounts['abstract'];
        $this->pendingCount     = $billCounts['pending'];
        $this->paidCount        = $billCounts['paid'];
        $this->dueCount         = $billCounts['due'];
        $this->delayBillCount   = $billCounts['delayBillCount'];
        $this->tatCount         = $billCounts['tat'];
        $this->overShotCount    = $billCounts['overShotCount'];
        $this->totalProjects    = $billCounts['totalProjects'];
        $this->totalSubProjects = $billCounts['totalSubProjects'];
        $this->totalPO          = $billCounts['totalPO'];
        $this->totalValidatePO  = $billCounts['totalValidatePO'];
        $this->totalWO          = $billCounts['totalWO'];
        $this->totalWOAmount    = $billCounts['totalWOAmount'];
        $this->totalValidateWO  = $billCounts['totalValidateWO'];
        $this->totalBill        = $billCounts['totalBill'];

        $paymentAmounts = $LevelDashboardDataService->getBillAmountSummary();
        $this->data['totalPaymentAmount'] = $paymentAmounts['totalAmount'];
        $this->data['pendingPaymentAmount'] = $paymentAmounts['pendingAmount'];

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
        
        $this->data['chartPaidVsUnpaidVsDelay'] =
        (new LevelDashboardDataService)->getPaidVsUnpaidVsDelay()->getData(true);
 
        return view('dashboard.vendor.index', $this->data);
    }
  
    
}
