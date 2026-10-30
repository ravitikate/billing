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
trait AccountDashboard
{

    /**
     */
    public function accountDashboard()
    {
        $user = user();

        $completedTaskColumn = TaskboardColumn::completeColumn();
        $showClockIn = attendance_setting();
        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;

        $endTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;

        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        if (!is_null($this->attendanceSettings->early_clock_in)) {
            $officeStartTime->subMinutes($this->attendanceSettings->early_clock_in);
        }

        // shift crosse
        if ($officeStartTime->gt($officeEndTime)) { // check if shift end time is less then current time then shift not ended yet
            if(
                now(company()->timezone)->lessThan($officeEndTime)
                || (now(company()->timezone)->greaterThan($officeEndTime) && now(company()->timezone)->lessThan($officeStartTime))
            ){
                $officeStartTime->subDay();

            }else{
                $officeEndTime->addDay();
            }
        }

        $this->cannotLogin = false;
        $date = now()->format('Y-m-d');

        $Utc = now(company()->timezone)->format('p');
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
            ->get();

        foreach ($attendance as $item) {
            if ($item->clock_out_time !== null && now()->between($item->clock_in_time, $item->clock_out_time)) { // if ($item->clock_out_time !== null && added this condition
                $this->cannotLogin = true;
                break;
            }
        }




        $currentDate = now();

        $this->checkJoiningDate = true;

        if (is_null(user()->employeeDetail->joining_date) || user()->employeeDetail->joining_date->gt($currentDate)) {
            $this->checkJoiningDate = false;
        }

        $this->viewEventPermission = user()->permission('view_events');
        $this->viewHolidayPermission = user()->permission('view_holiday');
        $this->viewTaskPermission = user()->permission('view_tasks');
        $this->viewTicketsPermission = user()->permission('view_tickets');
        $this->viewLeavePermission = user()->permission('view_leave');
        $this->viewNoticePermission = user()->permission('view_notice');
        $this->editTimelogPermission = user()->permission('edit_timelogs');

        // Getting Attendance setting data


        $this->totalProjects = Project::select('projects.id')
            ->where('completion_percent', '<>', 100)
            ->join('project_members', 'project_members.project_id', '=', 'projects.id')
            ->where('project_members.user_id', $this->user->id)
            ->distinct()
            ->count('projects.id');

        $this->counts = User::without(['clientDetails', 'employeeDetail', 'roles', 'session'])->withCount([
            'timeLogs as totalHoursLogged' => function ($query) {
                $query->select(DB::raw('IFNULL(sum(total_minutes), 0)'))
                    ->where('user_id', $this->user->id);
            },
            'tasks as totalCompletedTasks' => function ($query) use ($completedTaskColumn) {
                $query->where('tasks.board_column_id', $completedTaskColumn->id)
                    ->whereHas('taskUsers', function ($query) {
                        $query->where('user_id', $this->user->id);
                    });
            }
        ])
            ->findOrFail($this->user->id);

        if (!is_null($this->viewNoticePermission) && $this->viewNoticePermission != 'none') {
            if ($this->viewNoticePermission == 'added') {
                $this->notices = Notice::latest()->where('added_by', $this->user->id)
                    ->select('id', 'heading', 'created_at')
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'owned') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->where(['to' => 'employee', 'department_id' => null])
                    ->orWhere(['department_id' => $this->user->employeeDetail->department_id])
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'both') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->where('added_by', $this->user->id)
                    ->orWhere(function ($q) {
                        $q->where(['to' => 'employee', 'department_id' => null])
                            ->orWhere(['department_id' => $this->user->employeeDetail->department_id]);
                    })
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'all') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->limit(10)
                    ->get();
            }
        }


        $checkTicketAgent = TicketAgentGroups::select('id')->where('agent_id', user()->id)->first();

        if (!is_null($checkTicketAgent)) {
            $this->totalOpenTickets = Ticket::with('agent')->whereHas('agent', function ($q) {
                $q->where('id', user()->id);
            })->where('status', 'open')->count();
        }

        $tasks = $this->pendingTasks = Task::with(['activeProject', 'boardColumn', 'labels'])
            ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $this->user->id)
            ->where('tasks.board_column_id', '<>', $completedTaskColumn->id)
            ->select('tasks.*')
            ->distinct()
            ->orderBy('tasks.id', 'desc')
            ->get();

        $this->inProcessTasks = $tasks->count();

        $this->dueTasks = $tasks->filter(function ($item) {
            return !is_null($item->due_date) && $item->due_date->endOfDay()->isPast();
        })->count();

        $projects = Project::with('members')
            ->where('completion_percent', '<>', '100')
            ->leftJoin('project_members', 'project_members.project_id', 'projects.id')
            ->leftJoin('users', 'project_members.user_id', 'users.id')
            ->selectRaw('project_members.user_id, projects.deadline as due_date, projects.id')
            ->where('project_members.user_id', $this->user->id)
            ->groupBy('projects.id')
            ->get();

        $projects = $projects->whereNotNull('due_date');

        $this->dueProjects = $projects->filter(function ($value) {
            return now(company()->timezone)->gt($value->due_date);
        })->count();

        // Getting Current Clock-in if exist

        $defaultShift = EmployeeShift::where('id', $this->company->attendanceSetting->default_employee_shift)
            ->select('id', 'office_start_time', 'office_end_time')
            ->first();

        $Utc = now(company()->timezone)->format('p');
        $this->shiftStartDateTime = $officeStartTime;
        $this->shiftEndDateTime = $officeEndTime;

        // Fetch current clock-in record'd , to Link ATA
        if (!now(company()->timezone)->greaterThan($officeEndTime) && $showClockIn && $showClockIn->show_clock_in_button == 'no' || $showClockIn->show_clock_in_button == null){
            $this->currentClockIn = Attendance::whereNull('clock_out_time')
                ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
                ->where('user_id', $this->user->id)
                ->where(function ($query) use ($officeStartTime, $officeEndTime,$Utc) {
                    if ($this->attendanceSettings->shift_type == 'strict') {
                        $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [$officeStartTime, $officeEndTime]);

                    } else {
                        $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [now()->startOfDay(), now()->endOfDay()]);
                    }
                })
                ->first();
        }elseif ($showClockIn && $showClockIn->show_clock_in_button == 'yes' || $showClockIn->show_clock_in_button == null){
            $this->currentClockIn = Attendance::whereNull('clock_out_time')
                ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
                ->where('user_id', $this->user->id)
                ->whereDate(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), now($Utc)->format('Y-m-d'))
                ->first();

            if(!$this->currentClockIn){
                $this->currentClockIn = Attendance::whereNull('clock_out_time')
                    ->select('id', 'clock_in_time', 'clock_out_time', 'employee_shift_id')
                    ->where('user_id', $this->user->id)
                    ->where(function ($query) use ($officeStartTime, $officeEndTime,$Utc) {
                        $query->whereBetween(DB::raw("CONVERT_TZ(clock_in_time, '+00:00', '{$Utc}')"), [$officeStartTime, $officeEndTime]);
                    })
                    ->first();
            }
        }else{
            $this->currentClockIn = null;
            $this->cannotLogin = true;
        }

        //  $this->unapprovedInvoices = DB::table('invoices as i')
        //                     ->join('invoice_items as ii', 'ii.invoice_id', '=', 'i.id')
        //                     ->join('approval_items_by as ai', 'ai.invoice_item_id', '=', 'ii.id')
        //                     ->join('contracts as c', 'c.id', '=', 'i.contract_id')
        //                     ->join('projects as p', 'p.id', '=', 'i.project_id')
        //                     ->join('users as u', 'u.id', '=', 'i.client_id')
        //                     ->where('ai.status', 1)
        //                     ->distinct()
        //                     ->select('i.invoice_number','i.id as invoiceId','u.name', 'p.project_name', 'i.issue_date', 'i.due_date', 'c.subject', 'c.contract_number')
        //                     ->get();
        // $this->paidInvoices = DB::table('invoices as i')
        //                     ->join('invoice_items as ii', 'ii.invoice_id', '=', 'i.id')
        //                     ->join('approval_items_by as ai', 'ai.invoice_item_id', '=', 'ii.id')
        //                     ->join('contracts as c', 'c.id', '=', 'i.contract_id')
        //                     ->join('projects as p', 'p.id', '=', 'i.project_id')
        //                     ->join('users as u', 'u.id', '=', 'i.client_id')
        //                     ->where('i.status', 'paid')
        //                     ->distinct()
        //                     ->select('i.invoice_number','i.id as invoiceId','u.name', 'p.project_name', 'i.issue_date', 'i.due_date', 'c.subject', 'c.contract_number')
        //                     ->get();
        // $this->unpaidInvoices = DB::table('invoices as i')
        //                     ->join('invoice_items as ii', 'ii.invoice_id', '=', 'i.id')
        //                     ->join('approval_items_by as ai', 'ai.invoice_item_id', '=', 'ii.id')
        //                     ->join('contracts as c', 'c.id', '=', 'i.contract_id')
        //                     ->join('projects as p', 'p.id', '=', 'i.project_id')
        //                     ->join('users as u', 'u.id', '=', 'i.client_id')
        //                     ->where('i.status', 'unpaid')
        //                     ->distinct()
        //                     ->select('i.invoice_number','i.id as invoiceId','u.name', 'p.project_name', 'i.issue_date', 'i.due_date', 'c.subject', 'c.contract_number')
        //                     ->get();


        $this->event_filter = explode(',', user()->employeeDetail->calendar_view);
        $this->widgets = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $this->data['invoiceOverviewChart'] = $this->invoiceOverviewChart();

        return view('dashboard.account.index', $this->data);
    }

    public function invoiceOverviewChart()
{
    $data = [
        'values' => [],
        'colors' => [],
        'labels' => []
    ];

    $allInvoice = Invoice::get();

    // Draft invoices
    $data['values'][] = $allInvoice->where('status', 'draft')->count();
    $data['colors'][] = '#1d82f5';
    $data['labels'][] = __('modules.dashboard.invoiceDraft');

    // Not sent invoices
    $data['values'][] = $allInvoice->where('send_status', 0)->count();
    $data['colors'][] = '#4d4f5c';
    $data['labels'][] = __('modules.dashboard.invoiceNotSent');

    // Unpaid invoices
    $data['values'][] = $allInvoice->where('status', 'unpaid')->count();
    $data['colors'][] = '#D30000';
    $data['labels'][] = __('modules.dashboard.invoiceUnpaid');

    // Overdue invoices
    $data['values'][] = $allInvoice->filter(function ($invoice) {
        return ($invoice->status == 'unpaid' || $invoice->status == 'partial') && $invoice->due_date->lessThan(now());
    })->count();
    $data['colors'][] = '#99A5B5';
    $data['labels'][] = __('modules.dashboard.invoiceOverdue');

    // Partially paid invoices
    $data['values'][] = $allInvoice->where('status', 'partial')->count();
    $data['colors'][] = '#FCBD01';
    $data['labels'][] = __('modules.dashboard.invoicePartiallyPaid');

    // Paid invoices
    $data['values'][] = $allInvoice->where('status', 'paid')->count();
    $data['colors'][] = '#2CB100';
    $data['labels'][] = __('modules.dashboard.invoicePaid');

    return $data;
}

}
