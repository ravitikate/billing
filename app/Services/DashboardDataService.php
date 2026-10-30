<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    public function getPurchasePOcount(): array
    {
        return [
            'totalProjects'    => DB::table('projects')->count(),
            'totalSubProjects' => DB::table('tasks')->count(),
            'totalWO'          => DB::table('purchase_orders')->where('order_type', 'purchase_order')->count(),
            'totalValidateWO'  => DB::table('purchase_orders')->where('order_type', 'purchase_order')->where('validate', true)->count(),
        ];
    }

    public function getMonthlyPurchasePOVsValidatePOAmt(): array
    {
        $CRORE_DIVISOR = 10000000;
        $period = now()->subMonths(11)->monthsUntil(now());
        $months = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $months[$key] = [
                'label'     => $date->translatedFormat('F'),
                'total'     => 0,
                'validated' => 0,
            ];
        }

        $totalAmounts = DB::table('purchase_orders as po')
            ->join('purchase_items as pi', 'pi.purchase_order_id', '=', 'po.id')
            ->where('po.order_type', 'purchase_order')
            ->whereBetween('po.created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(po.created_at, '%Y-%m') as month, SUM(pi.amount + pi.gst_amount) as total_amount")
            ->groupBy('month')
            ->get();

        $validatedAmounts = DB::table('purchase_orders as po')
            ->join('purchase_items as pi', 'pi.purchase_order_id', '=', 'po.id')
            ->where('po.order_type', 'purchase_order')
            ->where('po.validate', true)
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
}
