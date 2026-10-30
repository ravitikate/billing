<?php
namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LevelDashboardDataService
{
    protected $userId;
    protected $projects;

    public function __construct()
    {
        $this->userId   = user()->id;
        $this->projects = Project::with('members')
            ->leftJoin('project_members', 'project_members.project_id', 'projects.id')
            ->leftJoin('users', 'project_members.user_id', 'users.id')
            ->where('project_members.user_id', $this->userId)
            ->get();
    }
    public function allCount(): array
    {
        $userId = $this->userId;
        $vendorId = null;
        if (in_array('vendor', user_roles())) {
            $vendor = DB::table('purchase_vendors')
                ->where('user_id', $userId)
                ->first();

            if ($vendor) {
                $vendorId = $vendor->id;
            }
        }

        $isVendor  = in_array('vendor', user_roles());
        $userKey   = $isVendor ? 'po.vendor_id' : 'pm.user_id';
        $userValue = $isVendor ? $vendorId : $userId;
        $billQuery = function () use ($isVendor, $vendorId, $userId) {
            $query = DB::table('purchase_bills as b')
                ->join('purchase_orders as po', 'po.id', '=', 'b.purchase_order_id');

            if ($isVendor) {
                return $vendorId
                    ? $query->where('b.purchase_vendor_id', $vendorId)
                    : $query->whereRaw('1 = 0');
            }

            return $query->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
                ->where('pm.user_id', $userId);
        };

        return [
            'totalBill'        => $billQuery()
                ->join('projects as p', 'p.id', '=', 'po.project_id') // join to get district
            // ->distinct()
                ->count('p.id'),

            'paid'             => $billQuery()
                ->where('b.status', 'paid')
                ->distinct()
                ->count('b.id'),

            'delayBillCount'   => $billQuery()
                ->where('b.status', '<>', 'paid')
                ->whereRaw('DATE_ADD(b.date_for_delay_count, INTERVAL po.billing_duration DAY) < CURDATE()')
                ->distinct()
                ->count('b.id'),

            'unpaidBillCount'  => $billQuery()
                ->where('b.status', '<>', 'paid')
                ->whereRaw('DATE_ADD(b.date_for_delay_count, INTERVAL po.billing_duration DAY) >= CURDATE()')
                ->distinct()
                ->count('b.id'),

            'due'              => $billQuery()
                ->where('b.status', 'due')
                ->distinct()
                ->count('b.id'),

            'abstract'         => $billQuery()
                ->when($isVendor, function ($query) {
                    return $query->whereIn('b.status', ['abstract', 'draft', 'abstract_reject']);
                }, function ($query) {
                    return $query->where('b.status', 'abstract');
                })
                ->distinct()
                ->count('b.id'),

            'pending'          => $billQuery()
                ->where('b.status', 'pending')
                ->distinct()
                ->count('b.id'),

            'tat'              => $billQuery()
                ->where('b.status', 'open')
                ->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) >= CURDATE()')
                ->distinct()
                ->count('b.id'),

            'overShotCount'    => $billQuery()
                ->where('b.status', 'open')
                ->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) < CURDATE()')
                ->distinct()
                ->count('b.id'),

            'totalProjects'    => DB::table('project_members')->where('user_id', $this->userId)->count(),
            'totalSubProjects' => $count = DB::table('project_members as pm')
                ->join('tasks as t', 't.project_id', '=', 'pm.project_id')
                ->where('pm.user_id', $this->userId)

                ->count('t.id'),

            'totalPO'          => DB::table('project_members as pm')
                ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
                ->where($userKey, $userValue)
                ->where('po.order_type', 'purchase_order')
                ->distinct()
                ->count('po.id'),

            'totalValidatePO'  => DB::table('project_members as pm')
                ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
                ->where('pm.user_id', $this->userId)
                ->where('po.order_type', 'purchase_order')
                ->where('po.validate', true)
                ->count('po.id'),

            'totalWO'          => DB::table('project_members as pm')
                ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
                ->where($userKey, $userValue)
                ->where('po.order_type', 'work_order')
                ->when($isVendor, function ($query) {
                    return $query->where('po.validate', true);
                })
                ->distinct()
                ->count('po.id'),

            'totalWOAmount'    => DB::table('purchase_orders as po')
                ->where('po.order_type', 'work_order')
                ->when($isVendor, function ($query) use ($vendorId) {
                    return $vendorId
                        ? $query->where('po.vendor_id', $vendorId)
                            ->where('po.validate', true)
                        : $query->whereRaw('1 = 0');
                }, function ($query) use ($userId) {
                    return $query->whereExists(function ($projectMembership) use ($userId) {
                        $projectMembership->selectRaw('1')
                            ->from('project_members as pm')
                            ->whereColumn('pm.project_id', 'po.project_id')
                            ->where('pm.user_id', $userId);
                    });
                })
                ->sum('po.total'),

            'totalValidateWO'  => DB::table('project_members as pm')
                ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
                ->where('pm.user_id', $this->userId)
                ->where('po.order_type', 'work_order')
                ->where('po.validate', true)
                ->count('po.id'),

        ];
    }
    public function review_action(): array
    {
        $userId = $this->userId;

        return [
            'pending' => DB::table('purchase_bills as pb')
                ->select(DB::raw('COUNT(DISTINCT pb.id) as pending_bill_count'))
                ->join('purchase_orders as po', 'pb.purchase_order_id', '=', 'po.id')
                ->join('projects as p', 'po.project_id', '=', 'p.id')
                ->join('approval_bills_by as abb', 'abb.bill_id', '=', 'pb.id')
                ->join('priority_role as pr', function ($join) {
                    $join->on('pr.project_id', '=', 'p.id')
                        ->on('pr.priority_id', '=', 'abb.approval_status');
                })
                ->where('pb.status', 'pending')
                ->where('pr.user_id', $userId)
                ->where(function ($query) use ($userId) {
                    $query->whereExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('project_members as pm')
                            ->whereColumn('pm.project_id', 'p.id')
                            ->where('pm.user_id', $userId);
                    })
                        ->orWhereExists(function ($subQuery) use ($userId) {
                            $subQuery->select(DB::raw(1))
                                ->from('purchase_vendors as pv')
                                ->whereColumn('pv.id', 'pb.purchase_vendor_id')
                                ->where('pv.user_id', $userId);
                        });
                })
                ->value('pending_bill_count'),
        ];
    }

    public function intatBillcounts()
    {
        $userId          = $this->userId;
        $intatBillcounts = DB::table('project_members as pm')
            ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
            ->join('purchase_bills as pb', 'pb.purchase_order_id', '=', 'po.id')
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->join('priority_role as pr', function ($join) {
                $join->on('pr.priority_id', '=', 'ab.approval_status')
                    ->on('pr.project_id', '=', 'pm.project_id');
            })
            ->where('pm.user_id', $userId)
            ->where('pb.status', 'pending')
            ->whereRaw('pr.priority_id < (
        SELECT priority_id
        FROM priority_role
        WHERE user_id = ?
          AND project_id = pm.project_id
        LIMIT 1
    )', [$userId])
            ->selectRaw("
        COUNT(DISTINCT CASE
            WHEN (pr.approval_unit = 'days'  AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time DAY)  < NOW())
              OR (pr.approval_unit = 'hours' AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time HOUR) < NOW())
            THEN pb.id
        END) AS overdue_pending_count,
        COUNT(DISTINCT CASE
            WHEN (pr.approval_unit = 'days'  AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time DAY)  >= NOW())
              OR (pr.approval_unit = 'hours' AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time HOUR) >= NOW())
            THEN pb.id
        END) AS intat_count
    ")
            ->first();
        return $intatBillcounts;
    }
    public function intatAndOvershotBills()
    {
        $userId = $this->userId;

        // Base query
        $baseQuery = DB::table('project_members as pm')
            ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
            ->join('purchase_bills as pb', 'pb.purchase_order_id', '=', 'po.id')
            ->join('purchase_vendors as v', 'v.id', '=', 'pb.purchase_vendor_id')
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->join('priority_role as pr', function ($join) {
                $join->on('pr.priority_id', '=', 'ab.approval_status')
                    ->on('pr.project_id', '=', 'pm.project_id');
            })
            ->join('purchase_settings as ps', DB::raw('1'), '=', 'ps.id')
            ->where('pm.user_id', $userId)
            ->where('pb.status', 'pending')
            ->whereRaw('pr.priority_id < (
            SELECT priority_id
            FROM priority_role
            WHERE user_id = ?
              AND project_id = pm.project_id
            ORDER BY priority_id ASC
            LIMIT 1
        )', [$userId]);

        // In-Time Bills
        $intatBills = (clone $baseQuery)
            ->whereRaw("
            ( (pr.approval_unit = 'days'  AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time DAY) >= NOW())
            OR (pr.approval_unit = 'hours' AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time HOUR) >= NOW()) )
        ")
            ->select(
                'po.order_type',
                'v.primary_name',
                DB::raw("CONCAT(ps.bill_prefix, ps.bill_number_separator, LPAD(pb.purchase_bill_number, ps.bill_number_digit, '0')) as purchase_bill_numberx"),
                'pb.*'
            )
            ->groupBy('pb.id')
            ->get();

        // Overshot Bills
        $overshotBills = (clone $baseQuery)
            ->whereRaw("
            ( (pr.approval_unit = 'days'  AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time DAY) < NOW())
            OR (pr.approval_unit = 'hours' AND DATE_ADD(pb.updated_at, INTERVAL pr.approval_time HOUR) < NOW()) )
        ")
            ->select(
                'po.order_type',
                'v.primary_name',
                DB::raw("CONCAT(ps.bill_prefix, ps.bill_number_separator, LPAD(pb.purchase_bill_number, ps.bill_number_digit, '0')) as purchase_bill_numberx"),
                'pb.*'
            )
            ->groupBy('pb.id')
            ->get();

        // Add net_payable for each bill
        foreach ($intatBills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        foreach ($overshotBills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        return [
            'intatBills'    => $intatBills,
            'overshotBills' => $overshotBills,
        ];
    }

    public function getBillsByStatus(string $status, ?string $tatCondition = null)
    {
        $userId = $this->userId;
        $vendorId = null;
        if (in_array('vendor', user_roles())) {
            $vendor = DB::table('purchase_vendors')
                ->where('user_id', $userId)
                ->first();

            if ($vendor) {
                $vendorId = $vendor->id;
            }
        }

        $isVendor  = in_array('vendor', user_roles());

        $query = DB::table('purchase_bills as b')
            ->join('purchase_vendors as v', 'v.id', '=', 'b.purchase_vendor_id')
            ->join('purchase_orders as po', 'po.id', '=', 'b.purchase_order_id')
            ->leftJoin('projects as p', 'p.id', '=', 'po.project_id')
            ->leftJoin('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->leftJoin('purchase_settings as ps', DB::raw('1'), '=', 'ps.id')
            ->select(
                'po.order_type',
                'po.project_id',
                'p.project_name',
                'v.primary_name',
                DB::raw("CONCAT(ps.bill_prefix, ps.bill_number_separator, LPAD(b.purchase_bill_number, ps.bill_number_digit, '0')) as purchase_bill_numberx"),
                'b.*',
                DB::raw('DATE_ADD(b.updated_at, INTERVAL 4 DAY) as updated_after_4_days')
            );
        if ($isVendor) {
            $query->when($vendorId, function ($query) use ($vendorId) {
                return $query->where('b.purchase_vendor_id', $vendorId);
            }, function ($query) {
                return $query->whereRaw('1 = 0');
            });
        } else {
            $query->where('pm.user_id', $userId);
        }
        if ($tatCondition === 'delay') {
            $query->join('approval_bills_by as ab', 'ab.bill_id', '=', 'b.id')
                ->join('priority_role as pr', function ($join) {
                    $join->on('pr.project_id', '=', 'pm.project_id')
                        ->on('pr.priority_id', '=', 'ab.approval_status');
                })
                ->join('users as u', 'u.id', '=', 'pr.user_id')
                ->addSelect('u.name as delayuname');
            $query->where('b.status', '<>', "paid"); // Not equal
        } elseif ($isVendor && $status === 'abstract') {
            $query->whereIn('b.status', ['abstract', 'draft', 'abstract_reject']);
        } else {
            $query->where('b.status', $status); // Equal
        }
        if ($status === 'paid' && ! $isVendor) {
            $query->whereDate('b.updated_at', '>=', \Carbon\Carbon::now()->subDays(7));
        }
        // Apply TAT filters if needed
        if ($tatCondition === 'within') {
            $query->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) >= CURDATE()');
        } elseif ($tatCondition === 'overshot') {
            $query->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) < CURDATE()');
        }
        if ($tatCondition === 'delay') {
            $query->whereRaw('DATE_ADD(b.date_for_delay_count, INTERVAL po.billing_duration DAY) < CURDATE()');
        }
        $query->groupBy('b.id');
        $bills = $query->get();

        foreach ($bills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        return $bills;
    }

    public function getBillAmountSummary(): array
    {
        $vendorId = DB::table('purchase_vendors')
            ->where('user_id', $this->userId)
            ->value('id');

        if (!$vendorId) {
            return [
                'totalAmount' => 0,
                'pendingAmount' => 0,
            ];
        }

        $payments = DB::table('purchase_payment_bills')
            ->selectRaw('purchase_bill_id, SUM(total_paid) as total_paid')
            ->groupBy('purchase_bill_id');

        $summary = DB::table('purchase_bills as b')
            ->leftJoinSub($payments, 'payments', function ($join) {
                $join->on('payments.purchase_bill_id', '=', 'b.id');
            })
            ->where('b.purchase_vendor_id', $vendorId)
            ->whereIn('b.status', ['open', 'partially_paid', 'paid'])
            ->selectRaw("COALESCE(SUM(b.netpayableamount), 0) as total_amount")
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN b.status IN ('open', 'partially_paid')
                        THEN b.netpayableamount - COALESCE(payments.total_paid, 0)
                        ELSE 0
                    END
                ), 0) as pending_amount
            ")
            ->first();

        return [
            'totalAmount' => $summary->total_amount,
            'pendingAmount' => $summary->pending_amount,
        ];
    }

    // public function generateMonthlyBillStats(): array
    // {
    //     $start = Carbon::now()->subMonths(11)->startOfMonth();
    //     $end   = Carbon::now()->endOfMonth();

    //     $months = [];
    //     $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

    //     foreach ($period as $date) {
    //         $monthKey          = $date->format('m-Y');
    //         $months[$monthKey] = [
    //             'y'           => $date->translatedFormat('F'),
    //             'totalAmount' => 0,
    //             'paidAmount'  => 0,
    //         ];
    //     }

    //     // $results = DB::table('purchase_bills')
    //     //     ->select('id', 'status', 'updated_at')
    //     //     ->where('status', '!=', 'abstract')
    //     //     ->get();
    //     $results = DB::table('purchase_bills as b')
    //         ->join('purchase_orders as po', 'po.id', '=', 'b.purchase_order_id')
    //         ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
    //         ->select('b.id', 'b.status', 'b.updated_at')
    //         ->where('b.status', '!=', 'abstract')
    //         ->where('pm.user_id', $this->userId)
    //         ->get();

    //     foreach ($results as $bill) {
    //         $billDate = Carbon::parse($bill->updated_at);
    //         $monthKey = $billDate->format('m-Y');

    //         if (! array_key_exists($monthKey, $months)) {
    //             continue;
    //         }

    //         $netPayable = method_exists($this, 'calculateNetPayableL3')
    //         ? $this->calculateNetPayableL3($bill->id)
    //         : 0;

    //         if ($bill->status === 'paid') {
    //             $months[$monthKey]['paidAmount'] += $netPayable;
    //         } else {
    //             $months[$monthKey]['totalAmount'] += $netPayable;
    //         }
    //     }

    //     return $months;
    // }
    public function generateMonthlyBillStats(): array
    {
        // 1. Define range: 11 months ago + current month = 12 months total
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $months = [];
        $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

        foreach ($period as $date) {
            // 'M-Y' produces short month name and 4-digit year (e.g., Dec-2025)
            $monthKey = $date->format('M-Y');

            $months[$monthKey] = [
                'y'           => $monthKey, // Label for your chart/table
                'totalAmount' => 0,
                'paidAmount'  => 0,
            ];
        }

        $results = DB::table('purchase_bills as b')
            ->join('purchase_orders as po', 'po.id', '=', 'b.purchase_order_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->select('b.id', 'b.status', 'b.updated_at')
            ->where('b.status', '!=', 'abstract')
            ->where('pm.user_id', $this->userId)
            ->whereBetween('b.updated_at', [$start, $end]) // Performance filter
            ->get();

        foreach ($results as $bill) {
            $billDate = Carbon::parse($bill->updated_at);
            $monthKey = $billDate->format('M-Y'); // Match the format used in initialization

            if (isset($months[$monthKey])) {
                $netPayable = method_exists($this, 'calculateNetPayableL3')
                    ? $this->calculateNetPayableL3($bill->id)
                    : 0;

                if ($bill->status === 'paid') {
                    $months[$monthKey]['paidAmount'] += $netPayable;
                } else {
                    $months[$monthKey]['totalAmount'] += $netPayable;
                }
            }
        }

        // Return as a indexed array (cleaner for JSON responses)
        return array_values($months);
    }

    private function calculateNetPayableL3($billId)
    {
        $bill_detail = DB::table('purchase_bills')->find($billId);

        $curr_bill = DB::table('purchase_bill_items')
            ->where('purchase_bills_id', $billId)
            ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount, SUM(retention_cost) as total_retention_cost')
            ->first();

        $releaseHoldAmt = DB::table('release_hold_amt')->where('release_bill_id', $billId)->pluck('hold_amt')->sum();

        $Currentsub_total      = $curr_bill->total_amount ?? 0;
        $Currenttax_total      = $curr_bill->total_tax_amount ?? 0;
        $Currentretention_cost = $curr_bill->total_retention_cost ?? 0;
        $Currenttotal          = $Currentsub_total + $Currenttax_total + $releaseHoldAmt;

        $AdvanceRecovery = $bill_detail->AdvanceRecovery ?? 0;
        $HoldBillAmt     = $bill_detail->HoldBillAmt ?? 0;
        $totalRecoveries = $AdvanceRecovery + $Currentretention_cost + $HoldBillAmt;

        $instant_debit = DB::table('instant_debit')
            ->where('bill_id', $billId)
            ->where('bill_status', 2)
            ->sum('instantdebitamount') ?? 0;

        $OtherDebits = collect([
            $bill_detail->SafetyDebit ?? 0,
            $bill_detail->QualityDebit ?? 0,
            $bill_detail->Housekeeping ?? 0,
            $bill_detail->PoorPerformance ?? 0,
            $bill_detail->OtherDebits ?? 0,
            $instant_debit,
        ])->sum();

        $TotalDebitAmount = ($bill_detail->Steel ?? 0) + ($bill_detail->Cement ?? 0) + ($bill_detail->Electricity ?? 0) + $OtherDebits;

        return $Currenttotal - $totalRecoveries - $TotalDebitAmount;
    }

    public function getMonthlyPurchasePOVsValidatePOAmt(): array
    {
        $CRORE_DIVISOR = 10000000;
        $period        = now()->subMonths(11)->monthsUntil(now());
        $months        = [];

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
            ->where('po.order_type', 'purchase_order')
            ->where('pm.user_id', $this->userId)
            ->whereBetween('po.created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(po.created_at, '%Y-%m') as month, SUM(pi.amount + pi.gst_amount) as total_amount")
            ->groupBy('month')
            ->get();

        $validatedAmounts = DB::table('purchase_orders as po')
            ->join('purchase_items as pi', 'pi.purchase_order_id', '=', 'po.id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->where('po.order_type', 'purchase_order')
            ->where('po.validate', true)
            ->where('pm.user_id', $this->userId)
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

    public function fetchBudgetData()
    {
        $budgetData = DB::table('budget_head as bh')
            ->leftJoin('budget_head_details as bhd', 'bhd.budget_head_id', '=', 'bh.id')
            ->leftJoin('budget_head_name as bn', 'bn.id', '=', 'bhd.budget_head')
            ->join('project_members as pm', 'pm.project_id', '=', 'bh.project_id')
            ->selectRaw('SUBSTRING_INDEX(bhd.wbs, "-", -1) AS wbs_suffix, bn.name, bn.type, SUM(bhd.budget_cost) AS total_budget_cost, bh.project_id')
            ->where('pm.user_id', $this->userId)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('bh.status', 2)
                        ->where('bh.validate', 2)
                        ->where('bhd.status', 2)
                        ->where('bhd.val', 2);
                })->orWhere(function ($q) {
                    $q->where('bh.status', 1)
                        ->where('bh.validate', 2)
                        ->where('bhd.status', 2)
                        ->where('bhd.val', 2);
                })->orWhere(function ($q) {
                    $q->where('bh.status', 1)
                        ->where('bh.validate', 1)
                        ->where('bhd.val', 1);
                });
            })
            ->groupBy(DB::raw('SUBSTRING_INDEX(bhd.wbs, "-", -1)'))
            ->orderByRaw('MIN(bhd.id)')
            ->get();

        $actualData = DB::table('purchase_bill_items as pbi')
            ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->where('pm.user_id', $this->userId)
            ->where('pbi.quantity', '>', 0)
            ->where('pb.status', 'open')
            ->selectRaw('
                        SUBSTRING_INDEX(SUBSTRING_INDEX(pbi.wbs, "-", -1), ".", 1) AS wbs_suffix,
                        SUM(pbi.tax_amount) AS total_tax_amount,
                        SUM(pbi.amount) AS total_amount
                    ')
            ->groupBy(DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(pbi.wbs, "-", -1), ".", 1)'))
            ->get();

        $committedData = DB::table('purchase_items as pi')
            ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->where('pm.user_id', $this->userId)
            ->where('pi.po_type', 'boq')
            ->where('po.type', 'boq')
            ->where('po.validate', 'true')
            ->where('po.status', 'true')
            ->selectRaw('
                        SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, "-", -1), ".", 1) AS wbs_suffix,
                        SUM(pi.amount) AS total_amount,
                        SUM(pi.gst_amount) AS total_gst_amount
                    ')
            ->groupBy(DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, "-", -1), ".", 1)'))
            ->get();

        // $actualData = DB::table('purchase_bill_items as pbi')
        //     ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
        //     ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
        //     ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
        //     ->where('pm.user_id', $this->userId)
        //     ->selectRaw('SUBSTRING_INDEX(pbi.wbs, "-", -1) AS wbs_suffix, SUM(pbi.tax_amount) AS total_tax_amount, SUM(pbi.amount) AS total_amount')
        //     ->groupBy(DB::raw('SUBSTRING_INDEX(pbi.wbs, "-", -1)'))
        //     ->get();

        // $committedData = DB::table('purchase_items as pi')
        //     ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
        //     ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
        //     ->where('pm.user_id', $this->userId)
        //     ->where('pi.po_type', 'boq')
        //     ->where('po.type', 'boq')
        //     ->where('po.validate', 'true')
        //     ->where('po.status', 'true')
        //     ->selectRaw('SUBSTRING_INDEX(pi.wbs, "-", -1) AS wbs_suffix, SUM(pi.amount) AS total_amount, SUM(pi.gst_amount) AS total_gst_amount')
        //     ->groupBy(DB::raw('SUBSTRING_INDEX(pi.wbs, "-", -1)'))
        //     ->get();

        $final = [];

        foreach ($budgetData as $budgetItem) {
            $wbs_suffix = $budgetItem->wbs_suffix;
            $committed  = $committedData->firstWhere('wbs_suffix', $wbs_suffix);
            $actual     = $actualData->firstWhere('wbs_suffix', $wbs_suffix);

            $committed_cost = ($committed->total_amount ?? 0) + ($committed->total_gst_amount ?? 0);
            $actual_cost    = ($actual->total_amount ?? 0) + ($actual->total_tax_amount ?? 0);

            $final[] = [
                'wbs_suffix'        => $wbs_suffix,
                'project_id'        => $budgetItem->project_id,
                'name'              => $budgetItem->name,
                'type'              => $budgetItem->type,
                'total_budget_cost' => $budgetItem->total_budget_cost,
                'committed_cost'    => $committed_cost,
                'actual_cost'       => $actual_cost,
            ];
        }

        return $final;
    }

    public function totalWBSGraph($final)
    {
        $labels     = collect();
        $budgetData = collect();
        $poData     = collect();
        $actualData = collect();

        foreach ($final as $item) {
            $labels->push($item['name']);
            $budgetData->push(round(($item['total_budget_cost'] ?? 0) / 10000000, 2));
            $poData->push(round(($item['committed_cost'] ?? 0) / 10000000, 2));
            $actualData->push(round(($item['actual_cost'] ?? 0) / 10000000, 2));
        }

        return [
            'labels'        => $labels,
            'values'        => [$budgetData, $poData, $actualData],
            'colors'        => ['#007bff', '#ffc107', '#28a745'],
            'name'          => ['Budget', 'Committed (PO)', 'Actual'],
            'tooltipLabels' => $labels->map(function ($label, $i) use ($budgetData, $poData, $actualData) {
                return [
                    'budget'    => 'Budget: ₹ ' . number_format($budgetData[$i], 2) . ' Cr',
                    'committed' => 'Committed: ₹ ' . number_format($poData[$i], 2) . ' Cr',
                    'actual'    => 'Actual: ₹ ' . number_format($actualData[$i], 2) . ' Cr',
                ];
            }),
        ];
    }

    public function getBudgetCostSummary()
    {
        $now = Carbon::now();

        // Current month
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth   = $now->copy()->endOfMonth()->toDateString();

        // Financial year: April 1st to March 31st
        $fyStart = $now->month < 4
            ? Carbon::create($now->year - 1, 4, 1)
            : Carbon::create($now->year, 4, 1);

        $fyEnd = $fyStart->copy()->addYear()->subDay();

        return DB::table('budget_head as bh')
            ->leftJoin('budget_head_details as bhd', 'bhd.budget_head_id', '=', 'bh.id')
            ->leftJoin('budget_head_name as bn', 'bn.id', '=', 'bhd.budget_head')
            ->join('project_members as pm', 'pm.project_id', '=', 'bh.project_id')
            ->where('pm.user_id', $this->userId)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('bh.status', 2)
                        ->where('bh.validate', 2)
                        ->where('bhd.status', 2)
                        ->where('bhd.val', 2);
                })->orWhere(function ($q) {
                    $q->where('bh.status', 1)
                        ->where('bh.validate', 2)
                        ->where('bhd.status', 2)
                        ->where('bhd.val', 2);
                })->orWhere(function ($q) {
                    $q->where('bh.status', 1)
                        ->where('bh.validate', 1)
                        ->where('bhd.val', 1);
                });
            })
            ->selectRaw("
            SUM(bhd.budget_cost) AS total_budget_cost_all,
            SUM(CASE WHEN bhd.created_at BETWEEN ? AND ? THEN bhd.budget_cost ELSE 0 END) AS total_budget_cost_current_month,
            SUM(CASE WHEN bhd.created_at BETWEEN ? AND ? THEN bhd.budget_cost ELSE 0 END) AS total_budget_cost_financial_year
        ", [
                $startOfMonth, $endOfMonth,
                $fyStart->toDateString(), $fyEnd->toDateString(),
            ])
            ->first();
    }

    public function intatUnderProcessBill()
    {
        $userId = $this->userId;

        // Subquery: project approval days calculation
        $projectApproval = DB::table('priority_role as pr')
            ->select('pr.project_id', 'pr.user_id', 'pr.priority_id')
            ->selectRaw("
            CASE
                WHEN SUM(CASE WHEN pr.approval_time IS NULL OR pr.approval_time = 0 THEN 0 ELSE 1 END) = 0
                    THEN 7
                ELSE GREATEST(
                    7,
                    SUM(
                        CASE
                            WHEN pr.approval_time IS NULL OR pr.approval_time = 0 THEN 0
                            WHEN pr.approval_unit = 'hours' THEN pr.approval_time / 24
                            ELSE pr.approval_time
                        END
                    )
                )
            END as project_approval_days
        ")
            ->groupBy('pr.project_id', 'pr.user_id', 'pr.priority_id');

        // Subquery: bills with project approval days
        $subQuery = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'pb.purchase_vendor_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->join('purchase_settings as ps', DB::raw('1'), '=', 'ps.id') // singleton
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->joinSub($projectApproval, 'pa', function ($join) {
                $join->on('pa.project_id', '=', 'po.project_id')
                    ->on('pa.priority_id', '=', 'ab.approval_status');
            })
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->where('pm.user_id', $userId)
            ->where('pb.status', 'pending')
            ->selectRaw("
            pb.id as bill_id,
            pb.updated_at,
            pa.project_approval_days
        ")
            ->groupBy('pb.id');

        // Outer query: count SLA buckets
        $slaCounts = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery)
            ->selectRaw("
            COUNT(CASE WHEN DATE_ADD(t.updated_at, INTERVAL t.project_approval_days DAY) < NOW() THEN 1 END) as overshot,
            COUNT(CASE WHEN DATE_ADD(t.updated_at, INTERVAL t.project_approval_days DAY) >= NOW() THEN 1 END) as intat
        ")
            ->first();

        return $slaCounts;
    }

    public function getBillsBySlaStatus(string $slaStatus)
    {
        $subQuery = DB::table('priority_role')
            ->select('project_id', 'user_id', 'priority_id')
            ->selectRaw("
            CASE
                WHEN SUM(CASE WHEN approval_time IS NULL OR approval_time = 0 THEN 0 ELSE 1 END) = 0
                    THEN 7
                ELSE GREATEST(
                    7,
                    SUM(
                        CASE
                            WHEN approval_time IS NULL OR approval_time = 0 THEN 0
                            WHEN approval_unit = 'hours' THEN approval_time / 24
                            ELSE approval_time
                        END
                    )
                )
            END as project_approval_days
        ")
            ->groupBy('project_id', 'user_id', 'priority_id');

        $query = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'pb.purchase_vendor_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->join('purchase_settings as ps', DB::raw('1'), '=', 'ps.id') // singleton settings
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->joinSub($subQuery, 'pa', function ($join) {
                $join->on('pa.project_id', '=', 'po.project_id')
                    ->on('pa.priority_id', '=', 'ab.approval_status');
            })
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->where('pb.status', 'pending')
            ->select(
                'pb.*',
                'pa.user_id',
                'pa.priority_id',
                'po.project_id',
                'po.id as purchase_order_id',
                'po.order_type',
                'pa.project_approval_days',
                'u.name',
                'ab.bill_id as bbId',
                DB::raw("CONCAT(ps.bill_prefix, ps.bill_number_separator, LPAD(pb.purchase_bill_number, ps.bill_number_digit, '0')) as purchase_bill_numberx"),
                'v.primary_name as primary_name'
            )
            ->selectRaw("
            CASE
                WHEN DATE_ADD(pb.updated_at, INTERVAL pa.project_approval_days DAY) < NOW()
                    THEN 'overshot'
                ELSE 'intat'
            END as sla_status
        ")
            ->having('sla_status', $slaStatus)
            ->groupBy('pb.id')
            ->orderBy('pb.id');

        $bills = $query->get();

        foreach ($bills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        return $bills;
    }
//     public function getPaidVsUnpaidVsDelay()
// {
//     $userId = auth()->id(); // or pass dynamically
//     $roles = user_roles($userId) ?? [];
//     $isVendor = in_array('vendor', $roles);

//     $userKey   = $isVendor ? 'po.vendor_id' : 'pm.user_id';
//     $userValue = $isVendor ? DB::table('purchase_vendors')->where('user_id', $userId)->value('id') : $userId;

//     // Paid bills per month
//     $paid = DB::table('project_members as pm')
//         ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
//         ->join('purchase_bills as b', 'b.purchase_order_id', '=', 'po.id')
//         ->where($userKey, $userValue)
//         ->where('b.status', 'paid')
//         ->selectRaw('MONTH(b.updated_at) as month, COUNT(DISTINCT b.id) as total')
//         ->groupByRaw('MONTH(b.updated_at)')
//         ->pluck('total', 'month');

// //  echo '<pre>';  print_r($paid);exit;
//     // Unpaid bills per month (not yet due)
//     $unpaid = DB::table('project_members as pm')
//         ->join('projects as p', 'p.id', '=', 'pm.project_id')
//         ->join('purchase_orders as po', 'po.project_id', '=', 'p.id')
//         ->join('purchase_bills as pb', 'pb.purchase_order_id', '=', 'po.id')
//         ->where($userKey, $userValue)
//         ->where('pb.status', '<>', 'paid')
//         ->whereRaw('DATE_ADD(pb.date_for_delay_count, INTERVAL po.billing_duration DAY) >= CURDATE()')
//         ->selectRaw('MONTH(pb.date_for_delay_count) as month, COUNT(DISTINCT pb.id) as total')
//         ->groupByRaw('MONTH(pb.date_for_delay_count)')
//         ->pluck('total', 'month');

//     // Delayed bills per month
//     $delayed = DB::table('purchase_bills_delay')
//         ->where('user_id', $userValue)
//         ->selectRaw('MONTH(delay_date) as month, COUNT(DISTINCT bill_id) as total')
//         ->groupByRaw('MONTH(delay_date)')
//         ->pluck('total', 'month');

//     // Generate last 12 months dynamically
//     $period = now()->subMonths(11)->monthsUntil(now());
//     $months = [];
//     foreach ($period as $date) {
//         $monthNum = (int)$date->format('n'); // 1-12
//         $months[$monthNum] = $date->translatedFormat('M'); // dynamic short month name
//     }

//     $paidData = [];
//     $unpaidData = [];
//     $delayedData = [];

//     foreach ($months as $m => $label) {
//         $paidData[]   = $paid[$m] ?? 0;
//         $unpaidData[] = $unpaid[$m] ?? 0;
//         $delayedData[] = $delayed[$m] ?? 0;
//     }

//     return response()->json([
//         'labels'  => array_values($months),
//         'paid'    => $paidData,
//         'unpaid'  => $unpaidData,
//         'delayed' => $delayedData
//     ]);
// }

    public function getPaidVsUnpaidVsDelay()
    {
        $userId   = auth()->id();
        $roles    = user_roles($userId) ?? [];
        $isVendor = in_array('vendor', $roles);

        $userKey   = $isVendor ? 'po.vendor_id' : 'pm.user_id';
        $userValue = $isVendor ? DB::table('purchase_vendors')->where('user_id', $userId)->value('id') : $userId;

        // Define the 12-month range
        $start = now()->subMonths(11)->startOfMonth();
        $end   = now()->endOfMonth();

        // 1. Paid bills: Grouped by Year-Month
        $paid = DB::table('project_members as pm')
            ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
            ->join('purchase_bills as b', 'b.purchase_order_id', '=', 'po.id')
            ->where($userKey, $userValue)
            ->where('b.status', 'paid')
            ->whereBetween('b.updated_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(b.updated_at, '%b-%Y') as month_year, COUNT(DISTINCT b.id) as total")
            ->groupByRaw("DATE_FORMAT(b.updated_at, '%b-%Y')")
            ->pluck('total', 'month_year');

        // 2. Unpaid bills (not yet due)
        $unpaid = DB::table('project_members as pm')
            ->join('projects as p', 'p.id', '=', 'pm.project_id')
            ->join('purchase_orders as po', 'po.project_id', '=', 'p.id')
            ->join('purchase_bills as pb', 'pb.purchase_order_id', '=', 'po.id')
            ->where($userKey, $userValue)
            ->where('pb.status', '<>', 'paid')
            ->whereBetween('pb.date_for_delay_count', [$start, $end])
            ->whereRaw('DATE_ADD(pb.date_for_delay_count, INTERVAL po.billing_duration DAY) >= CURDATE()')
            ->selectRaw("DATE_FORMAT(pb.date_for_delay_count, '%b-%Y') as month_year, COUNT(DISTINCT pb.id) as total")
            ->groupByRaw("DATE_FORMAT(pb.date_for_delay_count, '%b-%Y')")
            ->pluck('total', 'month_year');

        // 3. Delayed bills
        $delayed = DB::table('purchase_bills_delay')
            ->where('user_id', $userValue)
            ->whereBetween('delay_date', [$start, $end])
            ->selectRaw("DATE_FORMAT(delay_date, '%b-%Y') as month_year, COUNT(DISTINCT bill_id) as total")
            ->groupByRaw("DATE_FORMAT(delay_date, '%b-%Y')")
            ->pluck('total', 'month_year');

        // 4. Generate the 12-month structure with 'Dec-2025' format
        $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

        $labels      = [];
        $paidData    = [];
        $unpaidData  = [];
        $delayedData = [];

        foreach ($period as $date) {
            $key = $date->format('M-Y'); // Matches DATE_FORMAT(..., '%b-%Y')

            $labels[]      = $key;
            $paidData[]    = $paid[$key] ?? 0;
            $unpaidData[]  = $unpaid[$key] ?? 0;
            $delayedData[] = $delayed[$key] ?? 0;
        }

        return response()->json([
            'labels'  => $labels,
            'paid'    => $paidData,
            'unpaid'  => $unpaidData,
            'delayed' => $delayedData,
        ]);
    }
}
