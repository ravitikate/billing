<?php
namespace App\Traits;

use App\Models\DashboardWidget;
use App\Services\LevelDashboardDataService;
use Illuminate\Contracts\View\View;

/**
 *
 */
trait BudgetDashboard
{

    /**
     */
    public function budgetDashboard()
    {
        $this->viewEventPermission   = user()->permission('view_events');
        $this->viewHolidayPermission = user()->permission('view_holiday');
        $this->viewTaskPermission    = user()->permission('view_tasks');
        $this->viewTicketsPermission = user()->permission('view_tickets');
        $this->viewLeavePermission   = user()->permission('view_leave');
        $this->viewNoticePermission  = user()->permission('view_notice');
        $this->editTimelogPermission = user()->permission('edit_timelogs');

        $this->widgets       = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $LevelDashboardDataService = new LevelDashboardDataService();

        $this->totalProjects    = $LevelDashboardDataService->allCount()['totalProjects'];
        $this->totalSubProjects = $LevelDashboardDataService->allCount()['totalSubProjects'];
        $this->totalPO          = $LevelDashboardDataService->allCount()['totalPO'];
        $this->totalBill        = $LevelDashboardDataService->allCount()['totalBill'];

        $final = (new LevelDashboardDataService)->fetchBudgetData();

        // echo "<pre>";
        // print_r($final);exit;
        $buildItems = array_filter($final, fn($item) => $item['type'] === 'build');
        $devItems   = array_filter($final, fn($item) => $item['type'] === 'dev');
        $techItems  = array_filter($final, fn($item) => $item['type'] === 'tech');

        $buildTotal = array_sum(array_column($buildItems, 'total_budget_cost'));
        $devTotal   = array_sum(array_column($devItems, 'total_budget_cost'));
        $techTotal  = array_sum(array_column($techItems, 'total_budget_cost'));

        $this->data['budgetChartData'] = [
            'labels' => ['Build', 'Dev', 'Tech'],
            'totals' => [
                round($buildTotal),
                round($devTotal),
                round($techTotal),
            ],
        ];

        $this->data['graphBuild']        = (new LevelDashboardDataService)->totalWBSGraph($buildItems);
        $this->data['devItemsChart']     = (new LevelDashboardDataService)->totalWBSGraph($devItems);
        $this->data['techItemsChart']    = (new LevelDashboardDataService)->totalWBSGraph($techItems);
        $this->data['totalWBSGraph']     = (new LevelDashboardDataService)->totalWBSGraph($final);
        $this->data['budgetCostSummary'] = (new LevelDashboardDataService)->getBudgetCostSummary();

        return view('dashboard.budget.index', $this->data);
    }
}
