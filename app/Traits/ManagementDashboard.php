<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Event;
use App\Models\Leave;
use App\Models\Notice;
use App\Models\Ticket;
use App\Models\Holiday;
use App\Models\Project;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use App\Models\LeadAgent;
use App\Models\Attendance;
use App\Models\Appreciation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProjectTimeLog;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\TaskboardColumn;
use App\Models\AttendanceSetting;
use App\Models\TicketAgentGroups;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTimeLogBreak;
use App\Models\EmployeeShiftSchedule;
use App\Http\Requests\ClockIn\ClockInRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\DealFollowUp;
use App\Models\EmployeeShift;

/**
 *
 */
trait ManagementDashboard
{

    /**
     */
    public function managementDashboard()
    {
        $user = user();

        // $completedTaskColumn = TaskboardColumn::completeColumn();
        // $showClockIn = attendance_setting();
        // $this->attendanceSettings = $this->attendanceShift($showClockIn);

        // $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;

        // $endTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;

        // $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        // $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        // if (!is_null($this->attendanceSettings->early_clock_in)) {
        //     $officeStartTime->subMinutes($this->attendanceSettings->early_clock_in);
        // }

        // // shift crosse
        // if ($officeStartTime->gt($officeEndTime)) { // check if shift end time is less then current time then shift not ended yet
        //     if(
        //         now(company()->timezone)->lessThan($officeEndTime)
        //         || (now(company()->timezone)->greaterThan($officeEndTime) && now(company()->timezone)->lessThan($officeStartTime))
        //     ){
        //         $officeStartTime->subDay();

        //     }else{
        //         $officeEndTime->addDay();
        //     }
        // }

        // $this->cannotLogin = false;
        // $date = now()->format('Y-m-d');

        // $Utc = now(company()->timezone)->format('p');
        // $attendance = Attendance::where('user_id', $this->user->id)
        //     ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
        //     ->get();

        // foreach ($attendance as $item) {
        //     if ($item->clock_out_time !== null && now()->between($item->clock_in_time, $item->clock_out_time)) { // if ($item->clock_out_time !== null && added this condition
        //         $this->cannotLogin = true;
        //         break;
        //     }
        // }




        // $currentDate = now();

        // $this->checkJoiningDate = true;

        // if (is_null(user()->employeeDetail->joining_date) || user()->employeeDetail->joining_date->gt($currentDate)) {
        //     $this->checkJoiningDate = false;
        // }

        $this->viewEventPermission = user()->permission('view_events');
        $this->viewHolidayPermission = user()->permission('view_holiday');
        $this->viewTaskPermission = user()->permission('view_tasks');
        $this->viewTicketsPermission = user()->permission('view_tickets');
        $this->viewLeavePermission = user()->permission('view_leave');
        $this->viewNoticePermission = user()->permission('view_notice');
        $this->editTimelogPermission = user()->permission('edit_timelogs');
 $this->instantDebitPermission = user()->permission('instant_debit');
        // // Getting Attendance setting data


        // $this->totalProjects = Project::select('projects.id')
        //     ->where('completion_percent', '<>', 100)
        //     ->join('project_members', 'project_members.project_id', '=', 'projects.id')
        //     ->where('project_members.user_id', $this->user->id)
        //     ->distinct()
        //     ->count('projects.id');

        // $this->counts = User::without(['clientDetails', 'employeeDetail', 'roles', 'session'])->withCount([
        //     'timeLogs as totalHoursLogged' => function ($query) {
        //         $query->select(DB::raw('IFNULL(sum(total_minutes), 0)'))
        //             ->where('user_id', $this->user->id);
        //     },
        //     'tasks as totalCompletedTasks' => function ($query) use ($completedTaskColumn) {
        //         $query->where('tasks.board_column_id', $completedTaskColumn->id)
        //             ->whereHas('taskUsers', function ($query) {
        //                 $query->where('user_id', $this->user->id);
        //             });
        //     }
        // ])
        //     ->findOrFail($this->user->id);

        // if (!is_null($this->viewNoticePermission) && $this->viewNoticePermission != 'none') {
        //     if ($this->viewNoticePermission == 'added') {
        //         $this->notices = Notice::latest()->where('added_by', $this->user->id)
        //             ->select('id', 'heading', 'created_at')
        //             ->limit(10)
        //             ->get();
        //     }
        //     elseif ($this->viewNoticePermission == 'owned') {
        //         $this->notices = Notice::latest()
        //             ->select('id', 'heading', 'created_at')
        //             ->where(['to' => 'employee', 'department_id' => null])
        //             ->orWhere(['department_id' => $this->user->employeeDetail->department_id])
        //             ->limit(10)
        //             ->get();
        //     }
        //     elseif ($this->viewNoticePermission == 'both') {
        //         $this->notices = Notice::latest()
        //             ->select('id', 'heading', 'created_at')
        //             ->where('added_by', $this->user->id)
        //             ->orWhere(function ($q) {
        //                 $q->where(['to' => 'employee', 'department_id' => null])
        //                     ->orWhere(['department_id' => $this->user->employeeDetail->department_id]);
        //             })
        //             ->limit(10)
        //             ->get();
        //     }
        //     elseif ($this->viewNoticePermission == 'all') {
        //         $this->notices = Notice::latest()
        //             ->select('id', 'heading', 'created_at')
        //             ->limit(10)
        //             ->get();
        //     }
        // }


        // $checkTicketAgent = TicketAgentGroups::select('id')->where('agent_id', user()->id)->first();

        // if (!is_null($checkTicketAgent)) {
        //     $this->totalOpenTickets = Ticket::with('agent')->whereHas('agent', function ($q) {
        //         $q->where('id', user()->id);
        //     })->where('status', 'open')->count();
        // }

        // $tasks = $this->pendingTasks = Task::with(['activeProject', 'boardColumn', 'labels'])
        //     ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
        //     ->where('task_users.user_id', $this->user->id)
        //     ->where('tasks.board_column_id', '<>', $completedTaskColumn->id)
        //     ->select('tasks.*')
        //     ->distinct()
        //     ->orderBy('tasks.id', 'desc')
        //     ->get();

        // $this->inProcessTasks = $tasks->count();

        // $this->dueTasks = $tasks->filter(function ($item) {
        //     return !is_null($item->due_date) && $item->due_date->endOfDay()->isPast();
        // })->count();

        // $projects = Project::with('members')
        //     ->where('completion_percent', '<>', '100')
        //     ->leftJoin('project_members', 'project_members.project_id', 'projects.id')
        //     ->leftJoin('users', 'project_members.user_id', 'users.id')
        //     ->selectRaw('project_members.user_id, projects.deadline as due_date, projects.id')
        //     ->where('project_members.user_id', $this->user->id)
        //     ->groupBy('projects.id')
        //     ->get();

        // $projects = $projects->whereNotNull('due_date');

        // $this->dueProjects = $projects->filter(function ($value) {
        //     return now(company()->timezone)->gt($value->due_date);
        // })->count();

        // // Getting Current Clock-in if exist

        // $defaultShift = EmployeeShift::where('id', $this->company->attendanceSetting->default_employee_shift)
        //     ->select('id', 'office_start_time', 'office_end_time')
        //     ->first();

        // $Utc = now(company()->timezone)->format('p');
        // $this->shiftStartDateTime = $officeStartTime;
        // $this->shiftEndDateTime = $officeEndTime;

        // // Fetch current clock-in record'd , to Link ATA
        // if (!now(company()->timezone)->greaterThan($officeEndTime) && $showClockIn && $showClockIn->show_clock_in_button == 'no' || $showClockIn->show_clock_in_button == null){
        //     $this->currentClockIn = Attendance::whereNull('clock_out_time')
        //         ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
        //         ->where('user_id', $this->user->id)
        //         ->where(function ($query) use ($officeStartTime, $officeEndTime,$Utc) {
        //             if ($this->attendanceSettings->shift_type == 'strict') {
        //                 $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [$officeStartTime, $officeEndTime]);

        //             } else {
        //                 $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [now()->startOfDay(), now()->endOfDay()]);
        //             }
        //         })
        //         ->first();
        // }elseif ($showClockIn && $showClockIn->show_clock_in_button == 'yes' || $showClockIn->show_clock_in_button == null){
        //     $this->currentClockIn = Attendance::whereNull('clock_out_time')
        //         ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
        //         ->where('user_id', $this->user->id)
        //         ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
        //         ->first();

        //     if(!$this->currentClockIn){
        //         $this->currentClockIn = Attendance::whereNull('clock_out_time')
        //             ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
        //             ->where('user_id', $this->user->id)
        //             ->where(function ($query) use ($officeStartTime, $officeEndTime,$Utc) {
        //                 $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [$officeStartTime, $officeEndTime]);
        //             })
        //             ->first();
        //     }
        // }else{
        //     $this->currentClockIn = null;
        //     $this->cannotLogin = true;
        // }

        $this->total_bills = DB::table('purchase_bills')->count();
        $this->total_projects = DB::table('projects')->count();


        $this->purchase_bills_count  = DB::table('purchase_bills')
        ->whereIn('status', ['open', 'draft', 'abstract'])
        ->groupBy('status')
        ->select('status', DB::raw('COUNT(*) as count'))
        ->pluck('count', 'status');



        $this->event_filter = explode(',', user()->employeeDetail->calendar_view);
        $this->widgets = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $this->paidVsUnpaidChartData1 = $this->paidVsUnpaidChart1();
        $this->stagesVsBoqChart1Data1 = $this->stagesVsBoqChart1();
        $this->billChart1Data1 = $this->billChart1();

        return view('dashboard.management.index', $this->data);
    }
    public function paidVsUnpaidChart1()
    {
        $period = now()->subMonths(11)->monthsUntil(now());

        $startDate = $period->startDate->startOfMonth();
        $endDate = $period->endDate->endOfMonth();

        $months = [];

        foreach ($period as $periodData) {
            $months[$periodData->format('m-Y')] = [
                'y' => $periodData ? $periodData->translatedFormat('F') : null,
                'a' => 0, // Paid Count
                'b' => 0, // Unpaid Count
                'c' => 0, // Paid Amount
                'd' => 0  // Unpaid Amount
            ];
        }

        // Fetch Monthly Paid Invoice Data
        $paidInvoices = DB::table('invoices')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->select(
                DB::raw('COUNT(id) as count_data'),
                DB::raw('SUM(total) as sum_data'),
                DB::raw("DATE_FORMAT(updated_at, '%m-%Y') as date"),
                DB::raw('YEAR(updated_at) as year, MONTH(updated_at) as month')
            )
            ->groupBy('year', 'month')
            ->orderBy('updated_at')
            ->get()->keyBy('date');

        // Fetch Monthly Unpaid Invoice Data
        $unpaidInvoices = DB::table('invoices')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'unpaid')
            ->select(
                DB::raw('COUNT(id) as count_data'),
                DB::raw('SUM(total) as sum_data'),
                DB::raw("DATE_FORMAT(updated_at, '%m-%Y') as date"),
                DB::raw('YEAR(updated_at) as year, MONTH(updated_at) as month')
            )
            ->groupBy('year', 'month')
            ->orderBy('updated_at')
            ->get()->keyBy('date');

        // Prepare Data for Graph
        $graphData = [];

        foreach ($months as $key => $month) {
            $paidCount = $paidInvoices[$key]->count_data ?? 0;
            $unpaidCount = $unpaidInvoices[$key]->count_data ?? 0;
            $paidAmount = $paidInvoices[$key]->sum_data ?? 0;
            $unpaidAmount = $unpaidInvoices[$key]->sum_data ?? 0;

            $graphData[] = [
                'y' => $months[$key]['y'], // Month Name
                'c' => $paidAmount,  // Paid Amount
                'd' => $unpaidAmount, // Unpaid Amount
                'a' => $paidCount,  // Paid Count
                'b' => $unpaidCount  // Unpaid Count
            ];
        }

        $graphData = collect($graphData);

        // Format labels with count in brackets
        $data['labels'] = $graphData->pluck('y');
        $data['values'][] = $graphData->pluck('c'); // Paid Amount
        $data['values'][] = $graphData->pluck('d'); // Unpaid Amount
        $data['colors'] = ['#007bff', '#ff9f00']; // Blue for Paid, Orange for Unpaid
        $data['name'][] = __('app.paid_amount');
        $data['name'][] = __('app.unpaid_amount');

        // Append count inside labels for tooltip
        $data['tooltipLabels'] = $graphData->map(function ($item) {
            return [
                'paid' => __('app.paid_amount') . ': ' . number_format($item['c'], 2) . ' (' . $item['a'] . ')',
                'unpaid' => __('app.unpaid_amount') . ': ' . number_format($item['d'], 2) . ' (' . $item['b'] . ')'
            ];
        });

        return $data;
    }

    public function stagesVsBoqChart1()
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $months = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $key = $current->format('m-Y');
            $months[$key] = [
                'y' => $current->format('F'), // Month Name
                'a' => 0, // Paid Count
                'b' => 0, // Unpaid Count
            ];
            $current->addMonth();
        }

        // Fetch Monthly Paid Invoice Data
        $stages = DB::table('purchase_orders')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('type', 'stages')
            ->selectRaw("COUNT(id) as count_data, DATE_FORMAT(updated_at, '%m-%Y') as date")
            ->groupBy('date')
            ->pluck('count_data', 'date');

        // Fetch Monthly Unpaid Invoice Data
        $boq = DB::table('purchase_orders')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('type', 'boq')
            ->selectRaw("COUNT(id) as count_data, DATE_FORMAT(updated_at, '%m-%Y') as date")
            ->groupBy('date')
            ->pluck('count_data', 'date');

        // Prepare Data for Graph
        $graphData = $months->map(function ($month, $key) use ($stages, $boq) {
            return [
                'y' => $month['y'],
                'a' => $stages[$key] ?? 0, // Paid Count
                'b' => $boq[$key] ?? 0, // Unpaid Count
            ];
        })->values();

        // Format Labels
        $data = [
            'labels' => $graphData->pluck('y'),
            'values' => [$graphData->pluck('a'), $graphData->pluck('b')],
            'colors' => ['#007bff', '#ff9f00'], // Blue for Paid, Orange for Unpaid
            'name' => [__('app.stages'), __('app.boq')],
            'tooltipLabels' => $graphData->map(fn ($item) => [
                'stages' => __('app.stages') . ': ' . number_format($item['a'], 2) . ' (' . $item['a'] . ')',
                'boq' => __('app.boq') . ': ' . number_format($item['b'], 2) . ' (' . $item['b'] . ')'
            ])
        ];

        return $data;
    }

    public function billChart1()
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $months = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $key = $current->format('m-Y');
            $months[$key] = [
                'y' => $current->format('F'), // Month Name
                'a' => 0, // Paid Count
                'b' => 0, // Unpaid Count
            ];
            $current->addMonth();
        }

        // Fetch Monthly Paid Invoice Data
        $abstract = DB::table('purchase_bills')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'abstract')
            ->selectRaw("COUNT(id) as count_data, DATE_FORMAT(updated_at, '%m-%Y') as date")
            ->groupBy('date')
            ->pluck('count_data', 'date');

        // Fetch Monthly Unpaid Invoice Data
        $draft = DB::table('purchase_bills')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'draft')
            ->selectRaw("COUNT(id) as count_data, DATE_FORMAT(updated_at, '%m-%Y') as date")
            ->groupBy('date')
            ->pluck('count_data', 'date');

            $open = DB::table('purchase_bills')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->where('status', 'open')
            ->selectRaw("COUNT(id) as count_data, DATE_FORMAT(updated_at, '%m-%Y') as date")
            ->groupBy('date')
            ->pluck('count_data', 'date');

        // Prepare Data for Graph
        $graphData = $months->map(function ($month, $key) use ($abstract, $draft,$open) {
            return [
                'y' => $month['y'],
                'a' => $abstract[$key] ?? 0, // Paid Count
                'b' => $draft[$key] ?? 0, // Unpaid Count
                'c' => $open[$key] ?? 0, // Unpaid Count
            ];
        })->values();

        // Format Labels
        $data = [
            'labels' => $graphData->pluck('y'),
            'values' => [$graphData->pluck('a'), $graphData->pluck('b'),$graphData->pluck('c')],
            'colors' => ['#007bff', '#ff9f00', '#28a745'],
            'name' => [__('app.abstract'), __('app.draft'), __('app.open')],
            'tooltipLabels' => $graphData->map(fn ($item) => [
                'abstract' => __('app.abstract') . ': ' . number_format($item['a'], 2) . ' (' . $item['a'] . ')',
                'draft' => __('app.draft') . ': ' . number_format($item['b'], 2) . ' (' . $item['b'] . ')',
                'open' => __('app.open') . ': ' . number_format($item['c'], 2) . ' (' . $item['c'] . ')'
            ])
        ];

        return $data;
    }




}
