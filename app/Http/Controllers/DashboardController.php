<?php
namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\LeadPipeline;
use App\Models\Leave;
use App\Models\ProjectTimeLog;
use App\Models\ProjectTimeLogBreak;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\Ticket;
use App\Traits\AccountDashboard;
use App\Traits\BillDashboard;
use App\Traits\BudgetDashboard;
use App\Traits\ClientDashboard;
use App\Traits\ClientPanelDashboard;
use App\Traits\ContractDashboard;
use App\Traits\CurrencyExchange;
use App\Traits\EmployeeDashboard;
use App\Traits\FinanceDashboard;
use App\Traits\HRDashboard;
use App\Traits\Level1Dashboard;
use App\Traits\Level2Dashboard;
use App\Traits\Level3Dashboard;
use App\Traits\ManagementDashboard;
use App\Traits\OverviewDashboard;
use App\Traits\ProjectDashboard;
use App\Traits\PurchaseDashboard;
use App\Traits\TicketDashboard;
use App\Traits\VendorPanelDashboard;
use Carbon\Carbon;
use Modules\Purchase\Entities\PurchaseBill;
use Carbon\CarbonPeriod;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends AccountBaseController
{

    use AppBoot, CurrencyExchange, OverviewDashboard, Level1Dashboard, Level2Dashboard, Level3Dashboard, PurchaseDashboard, EmployeeDashboard, ProjectDashboard, ClientDashboard, HRDashboard, TicketDashboard, FinanceDashboard, ClientPanelDashboard, AccountDashboard, BillDashboard, ContractDashboard, BudgetDashboard, ManagementDashboard, VendorPanelDashboard;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dashboard';

        $this->middleware(function ($request, $next) {
            $this->viewOverviewDashboard = user()->permission('view_overview_dashboard');
            $this->viewProjectDashboard  = user()->permission('view_project_dashboard');
            $this->viewClientDashboard   = user()->permission('view_client_dashboard');
            $this->viewHRDashboard       = user()->permission('view_hr_dashboard');
            $this->viewTicketDashboard   = user()->permission('view_ticket_dashboard');
            $this->viewFinanceDashboard  = user()->permission('view_finance_dashboard');

            return $next($request);
        });

    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response|mixed|void
     */
    public function index()
    {

        $this->isCheckScript();

        //   dd(user()->permission('level_2_dashboard'));

        if (in_array('vendor', user_roles())) {
            return $this->VendorPanelDashboard();
        }

        if (in_array('employee', user_roles())) {
            //  if (user()->permission('account_dashboard_view') === 'all') {
            //     return $this->accountDashboard();
            // }

            // if (user()->permission('budget_dashboard_view') === 'all') {
            //     return $this->budgetDashboard();
            // }

            // if (user()->permission('contract_dashboard_view') === 'all') {
            //     return $this->contractDashboard();
            // }

            // if (user()->permission('bill_dashboard_view') === 'all') {
            //     return $this->billDashboard();
            // }

            // if (user()->permission('management_dashboard_view') === 'all') {
            //     return $this->managementDashboard();
            // }

            // if (user()->permission('purchase_dashboard') === 'all') {
            //     return $this->purchaseDashboard();
            // }

            // if (user()->permission('level_1_dashboard') === 'all') {
            //     return $this->level1Dashboard();
            // }

            // if (user()->permission('level_2_dashboard') === 'all') {
            //     return $this->level2Dashboard();
            // }

            // if (user()->permission('level_3_dashboard') === 'all') {
            //     return $this->level3Dashboard();
            // }

            return $this->employeeDashboard();
        }

        if (in_array('client', user_roles())) {
            return $this->clientPanelDashboard();
        }
    }

    public function widget(Request $request, $dashboardType)
    {
        $data = $request->except('_token');

        // Step 1: Reset all widgets' status to 0
        DashboardWidget::where('status', 1)
            ->where('dashboard_type', $dashboardType)
            ->update(['status' => 0]);

        // Step 2: Update the status to 1 for widgets present in the request
        if (! empty($data)) {
            DashboardWidget::where('dashboard_type', $dashboardType)
                ->whereIn('widget_name', array_keys($data))
                ->update(['status' => 1]);
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function checklist()
    {
        if (in_array('admin', user_roles())) {
            $this->isCheckScript();

            return view('dashboard.checklist', $this->data);
        }
    }

    /**
     * @return array|\Illuminate\Http\Response
     */
    public function memberDashboard()
    {
        abort_403(! in_array('employee', user_roles()));

        return $this->employeeDashboard();
    }

    public function advancedDashboard()
    {

        if (in_array('admin', user_roles()) || $this->sidebarUserPermissions['view_overview_dashboard'] == 4
            || $this->sidebarUserPermissions['view_project_dashboard'] == 4
            || $this->sidebarUserPermissions['view_client_dashboard'] == 4
            || $this->sidebarUserPermissions['view_hr_dashboard'] == 4
            || $this->sidebarUserPermissions['view_ticket_dashboard'] == 4
            || $this->sidebarUserPermissions['view_finance_dashboard'] == 4) {

            $tab = request('tab');

            switch ($tab) {
                case 'project':
                    $this->projectDashboard();
                    break;
                case 'client':
                    $this->clientDashboard();
                    break;
                case 'hr':
                    $this->hrDashboard();
                    break;
                case 'ticket':
                    $this->ticketDashboard();
                    break;
                case 'finance':
                    $this->financeDashboard();
                    break;
                default:
                    if (in_array('admin', user_roles()) || $this->sidebarUserPermissions['view_overview_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'overview';
                        $this->overviewDashboard();

                    } elseif ($this->sidebarUserPermissions['view_project_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'project';
                        $this->projectDashboard();

                    } elseif ($this->sidebarUserPermissions['view_client_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'client';
                        $this->clientDashboard();

                    } elseif ($this->sidebarUserPermissions['view_hr_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'hr';
                        $this->hrDashboard();

                    } elseif ($this->sidebarUserPermissions['view_finance_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'finance';
                        $this->ticketDashboard();

                    } else if ($this->sidebarUserPermissions['view_ticket_dashboard'] == 4) {
                        $this->activeTab = $tab ?: 'finance';
                        $this->financeDashboard();
                    }

                    break;
            }

            if (request()->ajax()) {
                return $this->returnAjax($this->view);
            }

            if (! isset($this->activeTab)) {
                $this->activeTab = $tab ?: 'overview';
            }

            return view('dashboard.admin', $this->data);
        }
    }

    public function accountUnverified()
    {
        return view('dashboard.unverified', $this->data);
    }

    public function weekTimelog()
    {
        $now                 = now(company()->timezone);
        $attndcSetting       = attendance_setting();
        $this->timelogDate   = $timelogDate   = Carbon::parse(request()->date);
        $this->weekStartDate = $now->copy()->startOfWeek($attndcSetting->week_start_from);
        $this->weekEndDate   = $this->weekStartDate->copy()->addDays(7);
        $this->weekPeriod    = CarbonPeriod::create($this->weekStartDate, $this->weekStartDate->copy()->addDays(6)); // Get All Dates from start to end date

        $this->dateWiseTimelogs     = ProjectTimeLog::dateWiseTimelogs($timelogDate->toDateString(), user()->id);
        $this->dateWiseTimelogBreak = ProjectTimeLogBreak::dateWiseTimelogBreak($timelogDate->toDateString(), user()->id);

        $this->weekWiseTimelogs     = ProjectTimeLog::weekWiseTimelogs($this->weekStartDate->copy()->toDateString(), $this->weekEndDate->copy()->toDateString(), user()->id);
        $this->weekWiseTimelogBreak = ProjectTimeLogBreak::weekWiseTimelogBreak($this->weekStartDate->toDateString(), $this->weekEndDate->toDateString(), user()->id);

        $html = view('dashboard.employee.week_timelog', $this->data)->render();

        return Reply::dataOnly(['html' => $html]);
    }

    public function privateCalendar()
    {
        if (request()->filter) {
            $employee_details                = EmployeeDetails::where('user_id', user()->id)->first();
            $employee_details->calendar_view = request()->filter ? request()->filter : null;
            $employee_details->save();
            session()->forget('user');
        }

        $startDate = Carbon::parse(request('start'));
        $endDate   = Carbon::parse(request('end'));

        // get calendar view current logined user
        $calendar_filter_array = explode(',', user()->employeeDetail->calendar_view);

        $eventData = [];

        if (! is_null(user()->permission('view_events')) && user()->permission('view_events') != 'none') {

            if (in_array('events', $calendar_filter_array)) {
                // Events
                $model = Event::with('attendee', 'attendee.user');

                $model->where(function ($query) {
                    $query->whereHas('attendee', function ($query) {
                        $query->where('user_id', user()->id);
                    });
                    $query->orWhere('added_by', user()->id);
                });

                $model->whereBetween('start_date_time', [$startDate->toDateString(), $endDate->toDateString()]);

                $events = $model->get();

                foreach ($events as $event) {
                    $eventData[] = [
                        'id'            => $event->id,
                        'title'         => $event->event_name,
                        'start'         => $event->start_date_time,
                        'end'           => $event->end_date_time,
                        'event_type'    => 'event',
                        'extendedProps' => ['bg_color' => $event->label_color, 'color' => '#fff', 'icon' => 'fa-calendar'],
                    ];
                }
            }

        }
        $user = user();
        if (! is_null(user()->permission('view_holiday')) && user()->permission('view_holiday') != 'none') {
            if (in_array('holiday', $calendar_filter_array)) {
                // holiday
                $holidays = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->where(function ($query) use ($user) {
                        $query->where(function ($subquery) use ($user) {
                            $subquery->where(function ($q) use ($user) {
                                $q->where('department_id_json', 'like', '%"' . $user->employeeDetail->department_id . '"%')
                                    ->orWhereNull('department_id_json');
                            });
                            $subquery->where(function ($q) use ($user) {
                                $q->where('designation_id_json', 'like', '%"' . $user->employeeDetail->designation_id . '"%')
                                    ->orWhereNull('designation_id_json');
                            });
                            $subquery->where(function ($q) use ($user) {
                                $q->where('employment_type_json', 'like', '%"' . $user->employeeDetail->employment_type . '"%')
                                    ->orWhereNull('employment_type_json');
                            });
                        });
                    });
                $holidays = $holidays->get();

                foreach ($holidays as $holiday) {
                    $eventData[] = [
                        'id'            => $holiday->id,
                        'title'         => $holiday->occassion,
                        'start'         => $holiday->date,
                        'end'           => $holiday->date,
                        'event_type'    => 'holiday',
                        'extendedProps' => ['bg_color' => '#1d82f5', 'color' => '#fff', 'icon' => 'fa-star'],
                    ];
                }
            }

        }

        if (! is_null(user()->permission('view_tasks')) && user()->permission('view_tasks') != 'none') {

            if (in_array('task', $calendar_filter_array)) {
                // tasks
                $completedTaskColumn = TaskboardColumn::completeColumn();

                $tasks = Task::with('boardColumn')
                    ->where('board_column_id', '<>', $completedTaskColumn->id)
                    ->whereHas('users', function ($query) {
                        $query->where('user_id', user()->id);
                    })
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate->toDateString(), $endDate->toDateString()]);

                        $q->orWhereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate->toDateString(), $endDate->toDateString()]);
                    })->get();

                foreach ($tasks as $task) {
                    $eventData[] = [
                        'id'            => $task->id,
                        'title'         => $task->heading,
                        'start'         => $task->start_date,
                        'end'           => $task->due_date ?: $task->start_date,
                        'event_type'    => 'task',
                        'extendedProps' => ['bg_color' => $task->boardColumn->label_color, 'color' => '#fff', 'icon' => 'fa-list'],
                    ];
                }
            }
        }

        if (! is_null(user()->permission('view_tickets')) && user()->permission('view_tickets') != 'none') {

            if (in_array('tickets', $calendar_filter_array)) {
                // tickets
                $tickets = Ticket::where('user_id', user()->id)
                    ->whereBetween(DB::raw('DATE(tickets.`updated_at`)'), [$startDate->toDateTimeString(), $endDate->endOfDay()->toDateTimeString()])->get();

                foreach ($tickets as $ticket) {
                    $eventData[] = [
                        'id'            => $ticket->ticket_number,
                        'title'         => $ticket->subject,
                        'start'         => $ticket->updated_at,
                        'end'           => $ticket->updated_at,
                        'event_type'    => 'ticket',
                        'extendedProps' => ['bg_color' => '#1d82f5', 'color' => '#fff', 'icon' => 'fa-ticket-alt'],
                    ];
                }
            }

        }

        if (! is_null(user()->permission('view_leave')) && user()->permission('view_leave') != 'none') {

            if (in_array('leaves', $calendar_filter_array)) {
                // approved leaves of all emoloyees with employee name
                $leaves = Leave::join('leave_types', 'leave_types.id', 'leaves.leave_type_id')
                    ->where('leaves.status', 'approved')
                    ->select('leaves.id', 'leaves.leave_date', 'leaves.status', 'leave_types.type_name', 'leave_types.color', 'leaves.leave_date', 'leaves.duration', 'leaves.status', 'leaves.user_id')
                    ->with('user')
                    ->whereBetween(DB::raw('DATE(leaves.`leave_date`)'), [$startDate->toDateString(), $endDate->toDateString()])
                    ->get();

                foreach ($leaves as $leave) {
                    $duration = ($leave->duration == 'half day') ? '( ' . __('app.halfday') . ' )' : '';

                    $eventData[] = [
                        'id'            => $leave->id,
                        'title'         => $duration . ' ' . $leave->user->name,
                        'start'         => $leave->leave_date->toDateString(),
                        'end'           => $leave->leave_date->toDateString(),
                        'event_type'    => 'leave',
                        /** @phpstan-ignore-next-line */
                        'extendedProps' => ['name' => 'Leave : ' . $leave->user->name, 'bg_color' => $leave->color, 'color' => '#fff', 'icon' => 'fa-plane-departure'],
                    ];
                }
            }
        }

        return $eventData;
    }

    public function getLeadStage($pipelineId)
    {
        $this->startDate = (request('startDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('startDate')) : now($this->company->timezone)->startOfMonth();
        $this->endDate   = (request('endDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('endDate')) : now($this->company->timezone);
        $startDate       = $this->startDate->toDateString();
        $endDate         = $this->endDate->toDateString();

        $this->leadPipelines = LeadPipeline::all();

        $this->leadStatusChart = $this->leadStatusChart($startDate, $endDate, $pipelineId);

        return $this->returnAjax('dashboard.ajax.lead-by-pipeline');

    }

    public function getStatus($type)
    {
        if ($type === 'intat') {
            $tat = $this->getBillsByStatus('open', 'within');
            return view('dashboard.common.intatBillTableAccount', compact('tat'));
        } elseif ($type === 'overshot') {
            $overShot = $this->getBillsByStatus('open', 'overshot');
            return view('dashboard.common.overshotBillTableAccount', compact('overShot'));
        } elseif ($type === 'withAccount') {
            $overShot = $this->getBillsByStatus('open');
            return view('dashboard.common.overshotBillTableAccount', compact('overShot'));
        } elseif ($type === 'intatStatus') {
            $tat = $this->intatAndOvershotBills('intat');
            return view('dashboard.common.intatforBillTable', compact('tat'));
        } elseif ($type === 'overshotStatus') {
            $overShot = $this->intatAndOvershotBills('overshot');
            return view('dashboard.common.overshotforBillTable', compact('overShot'));

        } elseif ($type === 'intatUnder') {
            $tat = $this->getBillsBySlaStatus('intat'); // assign to $tat to match view
            return view('dashboard.common.intat_under_process_table', compact('tat'));
        } elseif ($type === 'overshotUnder') {
            $overshot = $this->getBillsBySlaStatus('overshot'); // assign to $overshot to match view
            return view('dashboard.common.overshot_under_process_table', compact('overshot'));
        }elseif ($type == 'billStatus') {
            $overshot = $this->getPendingBillsWithApprover(); // assign to $pending to match view
            return view('dashboard.common.billStatus_table', compact('overshot'));
        }

        abort(404, 'Invalid status type');
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

public function getPendingBillsWithApprover()
{
    $userId = user()->id;
    $vendorId = null;
    $isVendor = in_array('vendor', user_roles());

    // Get vendor ID if user is a vendor
    if ($isVendor) {
        $vendor = DB::table('purchase_vendors')
            ->where('user_id', $userId)
            ->first();
        $vendorId = $vendor->id ?? null;
    }

    $query = PurchaseBill::select([
            'purchase_bills.id as bill_id',
            'purchase_bills.purchase_bill_number',
            'tasks.heading as sub_project_name',
            DB::raw("DATE_FORMAT(purchase_bills.bill_date, '%d %M %Y') as bill_date"),
            DB::raw("CONCAT('₹', FORMAT(purchase_bills.total, 0)) as total_amount"),
            'projects.project_name',
            'purchase_vendors.primary_name as vendor_name',
            DB::raw("(
                SELECT u.name
                FROM approval_bills_by abb
                JOIN priority_role pr ON abb.approval_status = pr.priority_id
                    AND pr.project_id = projects.id
                JOIN users u ON pr.user_id = u.id
                WHERE abb.bill_id = purchase_bills.id
                ORDER BY abb.id DESC
                LIMIT 1
            ) as current_employee")
        ])
        ->join('purchase_orders', 'purchase_bills.purchase_order_id', '=', 'purchase_orders.id')
        ->join('projects', 'purchase_orders.project_id', '=', 'projects.id')
        ->leftJoin('purchase_vendors', 'purchase_bills.purchase_vendor_id', '=', 'purchase_vendors.id')
        ->leftJoin('tasks', function ($join) {
            $join->on('tasks.project_id', '=', 'projects.id')
                 ->whereRaw('tasks.id = (
                     SELECT MIN(t2.id)
                     FROM tasks t2
                     WHERE t2.project_id = projects.id
                 )'); // Get first task to avoid duplicates
        })
        ->where('purchase_bills.status', 'pending');

    // Apply vendor/user filtering like in getBillsByStatus()
    if ($isVendor && $vendorId) {
        $query->where('purchase_bills.purchase_vendor_id', $vendorId);
    } elseif (!$isVendor) {
        // For non-vendor users, check if they are project members
        $query->whereExists(function ($subQuery) use ($userId) {
            $subQuery->select(DB::raw(1))
                ->from('project_members')
                ->whereColumn('project_members.project_id', 'projects.id')
                ->where('project_members.user_id', $userId);
        });
    }

    $bills = $query->orderBy('purchase_bills.bill_date', 'DESC')
        ->orderBy('purchase_bills.id', 'DESC')
        ->get()
        ->unique('bill_id'); // Ensure unique bills

    foreach ($bills as $bill) {
        $bill->net_payable = $this->calculateNetPayableL3($bill->bill_id); // Note: changed from $bill->id
    }

    return $bills;
}

    public function getBillsByStatus(string $status, ?string $tatCondition = null)
    {
        $userId   = user()->id;
        $vendorId = null;

        // Check if user is a vendor
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

        $query = DB::table('project_members as pm')
            ->join('purchase_orders as po', 'po.project_id', '=', 'pm.project_id')
            ->join('purchase_bills as b', 'b.purchase_order_id', '=', 'po.id')
            ->join('purchase_vendors as v', 'v.id', '=', 'b.purchase_vendor_id')
            ->join('projects as p', 'p.id', '=', 'pm.project_id')
            ->join('tasks as t', 't.project_id', '=', 'p.id')
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'b.id')
            ->joinSub($subQuery, 'pa', function ($join) {
                $join->on('pa.project_id', '=', 'po.project_id')
                    ->on('pa.priority_id', '=', 'ab.approval_status');
            })
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->where('b.status', $status)
            ->where($userKey, $userValue)
            ->select(
                'po.order_type',
                'v.primary_name',
                'u.name as assigned_to',
                'p.project_name',
                't.heading as sub_project_name',
                'b.*',
                DB::raw('DATE_ADD(b.updated_at, INTERVAL 4 DAY) as updated_after_4_days')
            );

        if ($tatCondition === 'within') {
            $query->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) >= CURDATE()');
        }
        if ($tatCondition === 'overshot') {
            $query->whereRaw('DATE_ADD(b.updated_at, INTERVAL 15 DAY) < CURDATE()');
        }

        $query->groupBy('b.id');
        $bills = $query->get();

        // Calculate net payable
        foreach ($bills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        return $bills;
    }
      public function getBillsBySlaStatus(string $slaStatus)
    {
        $userId   = user()->id;
        $vendorId = null;

        // Check if user is a vendor
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
            ->join('projects as p', 'p.id', '=', 'pm.project_id')
            ->join('tasks as t', 't.project_id', '=', 'p.id')
            ->join('purchase_settings as ps', DB::raw('1'), '=', 'ps.id') // singleton settings
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->joinSub($subQuery, 'pa', function ($join) {
                $join->on('pa.project_id', '=', 'po.project_id')
                    ->on('pa.priority_id', '=', 'ab.approval_status');
            })
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->where('pb.status', 'pending')
            // Add user filtering based on user role
            ->where(function($q) use ($isVendor, $userKey, $userValue, $userId) {
                if ($isVendor) {
                    // For vendor users, filter by vendor_id
                    $q->where('po.vendor_id', $userValue);
                } else {
                    // For regular users, filter by project membership AND approval user
                    $q->where('pm.user_id', $userId)
                    ->orWhere('pa.user_id', $userId);
                }
            })
            ->select(
                'p.project_name',
                't.heading as sub_project_name',
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
    
  public function intatAndOvershotBills(string $slaStatus = null)
    {
        $userId = user()->id;

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

        // SubQuery: bills with SLA days
        $subQuery = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'pb.purchase_vendor_id')
            ->join('projects as p', 'p.id', '=', 'pm.project_id')
            ->join('tasks as t', 't.project_id', '=', 'p.id')
            ->leftJoin('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->leftJoinSub($projectApproval, 'pa', function ($join) {
                $join->on('pa.project_id', '=', 'po.project_id')
                    ->on('pa.priority_id', '=', 'ab.approval_status');
            })
            ->join('users as u', 'u.id', '=', 'pa.user_id')
            ->where('pm.user_id', $userId)
            ->where(function ($q) {
                $q->where('pb.status', 'pending')
                    ->orWhere('pb.status', 'open');
            })
            ->selectRaw("
            pb.*,
            v.primary_name,
            p.project_name,
            u.name as current_employee,
            t.heading as sub_project_name,
            CASE
                WHEN pb.status = 'pending' THEN pa.project_approval_days
                WHEN pb.status = 'open' THEN 15
            END as project_approval_days
        ")
            ->groupBy('pb.id', 'pb.updated_at', 'pa.project_approval_days');

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery);

        // Apply SLA filter if passed
        if ($slaStatus === 'overshot') {
            $query->whereRaw("DATE_ADD(t.updated_at, INTERVAL t.project_approval_days DAY) < CURDATE()");
        } elseif ($slaStatus === 'intat') {
            $query->whereRaw("DATE_ADD(t.updated_at, INTERVAL t.project_approval_days DAY) >= CURDATE()");
        }

        $bills = $query->get();

        // Attach net payable
        foreach ($bills as $bill) {
            $bill->net_payable = $this->calculateNetPayableL3($bill->id);
        }

        return $bills;
    }


}
