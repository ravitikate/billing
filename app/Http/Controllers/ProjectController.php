<?php

namespace App\Http\Controllers;

use App\DataTables\ArchiveProjectsDataTable;
use App\DataTables\ArchiveTasksDataTable;
use App\DataTables\DiscussionDataTable;
use App\DataTables\ExpensesDataTable;
use App\DataTables\InvoicesDataTable;
use App\DataTables\OrdersDataTable;
use App\DataTables\PaymentsDataTable;
use App\DataTables\ProjectNotesDataTable;
use App\DataTables\ProjectsDataTable;
use App\DataTables\TasksDataTable;
use App\DataTables\TicketDataTable;
use App\DataTables\TimeLogsDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Project\StoreProject;
use App\Http\Requests\Project\UpdateProject;
use App\Imports\ProjectImport;
use App\Jobs\ImportProjectJob;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\DiscussionCategory;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MessageSetting;
use App\Models\Payment;
use App\Models\Pinned;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectCategory;
use App\Models\ProjectFile;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectNote;
use App\Models\ProjectStatusSetting;
use App\Models\ProjectTemplate;
use App\Models\ProjectTimeLog;
use App\Models\ProjectTimeLogBreak;
use App\Models\SubTask;
use App\Models\SubTaskFile;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\TaskboardColumn;
use App\Models\Team;
use App\Models\User;
use App\Scopes\ActiveScope;
use App\Traits\ImportExcel;
use App\Traits\ProjectProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;
use App\Imports\BudgetHeadImport;
use App\Jobs\ImportBudgetHeadJob;
use App\Models\BudgetHead;
use App\Models\BudgetHeadDetail;
use App\DataTables\BudgetHeadDetailsDataTable;
use App\DataTables\CompanyGroupDataTable;
use App\DataTables\BillingCompanyDataTable;
use Modules\Purchase\Entities\PurchaseOrder;
use App\Imports\POOrderImport;
use App\Jobs\ImportPOOrderJob;
use App\Imports\StagesImport;
use App\Jobs\ImportStagesJob;
use App\DataTables\ProjectwiseBudgetHeadDataTable;
use App\DataTables\ProjectwisePOBillDataTable;
use App\DataTables\ProjectwisePODataTable;
use Illuminate\Support\Facades\Log;

use App\Models\BaseModel;

class ProjectController extends AccountBaseController
{

    use ProjectProgress, ImportExcel;

    private $onlyTrashedRecords = true;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.projects';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('projects', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProjectsDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_projects');
        abort_403((!in_array($viewPermission, ['all', 'added', 'owned', 'both'])));

        if (!request()->ajax()) {

            if (in_array('client', user_roles())) {
                $this->clients = User::client();
            }
            else {
                $this->clients = User::allClients();
                $this->allEmployees = User::allEmployees(null, true, ($viewPermission == 'all' ? 'all' : null));
            }

            $this->categories = ProjectCategory::all();
            $this->departments = Team::all();
            $this->projectStatus = ProjectStatusSetting::where('status', 'active')->get();
        }

        return $dataTable->render('projects.index', $this->data);

    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'archive':
            $this->archiveRecords($request);

            return Reply::success(__('messages.projectArchiveSuccessfully'));
        case 'change-status':
            $this->changeStatus($request);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_projects') != 'all');

        Project::withTrashed()->whereIn('id', explode(',', $request->row_ids))->forceDelete();

        $items = explode(',', $request->row_ids);

        foreach ($items as $item) {
            // Delete project files
            Files::deleteDirectory(ProjectFile::FILE_PATH . '/' . $item);
        }
    }

    protected function archiveRecords($request)
    {
        abort_403(user()->permission('edit_projects') != 'all');

        Project::whereIn('id', explode(',', $request->row_ids))->delete();
    }

    public function archiveDestroy($id)
    {
        Project::destroy($id);

        return Reply::success(__('messages.projectArchiveSuccessfully'));
    }

    protected function changeStatus($request)
    {


        abort_403(user()->permission('edit_projects') != 'all');

        Project::whereIn('id', explode(',', $request->row_ids))->update(['status' => $request->status]);
    }

    public function updateStatus(Request $request, $id)
    {

        Project::findOrFail($id)
            ->update([
                'status' => $request->status,
            ]);

        return Reply::success(__('messages.updateSuccess'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project = Project::withTrashed()->findOrFail($id);
        $this->deletePermission = user()->permission('delete_projects');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $project->added_by == user()->id)));

        // Delete project files
        Files::deleteDirectory(ProjectFile::FILE_PATH . '/' . $id);
        $project->forceDelete();

        return Reply::success(__('messages.deleteSuccess'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_projects');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->pageTitle = __('app.addProject');
        $this->clients = User::allClients(null, false, ($this->addPermission == 'all' ? 'all' : null));
        $this->categories = ProjectCategory::all();
        $this->templates = ProjectTemplate::all();
        $this->currencies = Currency::all();
        $this->teams = Team::all();
        $this->employees = User::allEmployees(null, false, ($this->addPermission == 'all' ? 'all' : null));
        $this->redirectUrl = request()->redirectUrl;

        $this->project = (request()['duplicate_project']) ? Project::with('client', 'members', 'members.user', 'members.user.session', 'members.user.employeeDetail.designation', 'milestones', 'milestones.currency')->withTrashed()->findOrFail(request()['duplicate_project'])->withCustomFields() : null;

        if ($this->project) {
            $this->projectMembers = $this->project->members ? $this->project->members->pluck('user_id')->toArray() : null;
        }

        $this->projectTemplate = request('template') ? ProjectTemplate::with('projectMembers')->findOrFail(request('template')) : null;

        if ($this->projectTemplate) {
            $this->projectTemplateMembers = $this->projectTemplate->projectMembers ? $this->projectTemplate->projectMembers->pluck('id')->toArray() : null;
        }

        $project = new Project();

        $getCustomFieldGroupsWithFields = $project->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        if (in_array('client', user_roles())) {
            $this->client = User::withoutGlobalScope(ActiveScope::class)->findOrFail(user()->id);

        }
        else {
            $this->client = isset(request()->default_client) ? User::withoutGlobalScope(ActiveScope::class)->findOrFail(request()->default_client) : null;
        }

        $userData = [];

        $usersData = $this->employees;

        foreach ($usersData as $user) {

            $url = route('employees.show', [$user->id]);

            $userData[] = ['id' => $user->id, 'value' => $user->name, 'image' => $user->image_url, 'link' => $url];

        }

        $this->userData = $userData;

        $this->view = 'projects.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('projects.create', $this->data);

    }

    /**
     * @param StoreProject $request
     * @return array|mixed
     * @throws \Throwable
     */
 public function store(Request $request)
{
     
    $this->addPermission = user()->permission('add_projects');
    abort_403(!in_array($this->addPermission, ['all', 'added']));

    DB::beginTransaction();

    try {
        $startDate = companyToYmd($request->start_date);
        $deadline = !$request->has('without_deadline') ? companyToYmd($request->deadline) : null;
        $now = now();
        $data = [
            'project_name' => $request->project_name,
            'project_short_code' => $request->project_code,
            'start_date' => $startDate,
            'deadline' => $deadline,
            'client_id' => $request->client_id,
            'hash' => $request->_token, 
            'added_by' => user()->id,
            'company_id' => company()->id,
            'created_at' => now(),
            'updated_at' => now()
        ];

        if (!is_null($request->duplicateProjectID)) {
            $duplicate = DB::table('projects')->where('id', $request->duplicateProjectID)->first();

            if ($duplicate) {
                $data = array_merge($data, [
                    'project_summary' => trim_editor($duplicate->project_summary),
                    'category_id' => $duplicate->category_id,
                    'client_view_task' => $duplicate->client_view_task,
                    'allow_client_notification' => $duplicate->allow_client_notification,
                    'manual_timelog' => $duplicate->manual_timelog,
                    'team_id' => $duplicate->team_id,
                    'status' => 'not started',
                    'project_budget' => $duplicate->project_budget,
                    'currency_id' => $duplicate->currency_id,
                    'hours_allocated' => $duplicate->hours_allocated,
                    'notes' => trim_editor($duplicate->notes)
                ]);
            }
        } else {
            $data['project_summary'] = trim_editor($request->project_summary);
            $data['category_id'] = $request->category_id ?: null;
            $data['client_view_task'] = $request->client_view_task ? 'enable' : 'disable';
            $data['allow_client_notification'] = $request->client_task_notification ? 'enable' : 'disable';
            $data['manual_timelog'] = $request->manual_timelog ? 'enable' : 'disable';
            $data['project_budget'] = $request->project_budget;
            $data['currency_id'] = $request->currency_id ?: company()->currency_id;
            $data['hours_allocated'] = $request->hours_allocated;
            $data['miro_board_id'] = $request->miro_board_id;
            $data['client_access'] = $request->has('client_access') ? 1 : 0;
            $data['enable_miroboard'] = $request->has('miroboard_checkbox') ? 1 : 0;
            $data['notes'] = trim_editor($request->notes);

            $defaultStatus = DB::table('project_status_settings')->where('default_status', 1)->first();
            if ($defaultStatus) {
                $data['status'] = $defaultStatus->status_name;
            }
        }
        
       

        $data['public'] = $request->public ? 1 : 0;

        $projectId = DB::table('projects')->insertGetId($data);

        if ($request->has('team_id') && is_array($request->team_id)) {
            foreach ($request->team_id as $teamId) {
                DB::table('project_departments')->insert([
                    'project_id' => $projectId,
                    'team_id' => $teamId
                ]);
            }
        }
        
          $userIds = $request->input('user_id', []);
        foreach ($userIds as $userId) {
            DB::table('project_members')->insert([
                'project_id' => $projectId,
                'user_id'    => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (trim_editor($request->notes) != '') {
            DB::table('project_notes')->insert([
                'project_id' => $projectId,
                'title' => 'Note',
                'details' => $request->notes,
                'client_id' => $request->client_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->logSearchEntry($projectId, $request->project_name, 'projects.show', 'project');
        $this->logProjectActivity($projectId, 'messages.addedAsNewProject');

        // template and duplication logic using Eloquent skipped (can be refactored similarly)

        DB::commit();

        $redirectUrl = urldecode($request->redirect_url) ?: route('projects.index');

        return Reply::dataOnly(['projectID' => $projectId, 'redirectUrl' => $redirectUrl]);

    } catch (TransportException $e) {
        DB::rollback();
        return Reply::error('Please configure SMTP details: ' . $e->getMessage(), 'smtp_error');
    } catch (\Exception $e) {
        DB::rollback();
        return Reply::error('Error occurred: ' . $e->getMessage());
    }
}



    public function edit($id)
    {
        $this->project = Project::with('client', 'members', 'members.user', 'members.user.session', 'members.user.employeeDetail.designation', 'milestones', 'milestones.currency')
            ->withTrashed()
            ->findOrFail($id)
            ->withCustomFields();

        $memberIds = $this->project->members->pluck('user_id')->toArray();

        $this->editPermission = user()->permission('edit_projects');
        $this->editProjectMembersPermission = user()->permission('edit_project_members');

        abort_403(!(
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && user()->id == $this->project->added_by)
            || ($this->editPermission == 'owned' && user()->id == $this->project->client_id && in_array('client', user_roles()))
            || ($this->editPermission == 'owned' && in_array(user()->id, $memberIds) && in_array('employee', user_roles()))
            || ($this->editPermission == 'both' && (user()->id == $this->project->client_id || user()->id == $this->project->added_by))
            || ($this->editPermission == 'both' && in_array(user()->id, $memberIds) && in_array('employee', user_roles()))
        ));

        $this->pageTitle = __('app.update') . ' ' . __('app.subProject');

        $getCustomFieldGroupsWithFields = $this->project->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->clients = User::allClients(null, false, ($this->editPermission == 'all' ? 'all' : null));
        $this->categories = ProjectCategory::all();
        $this->currencies = Currency::all();
        $this->teams = Team::all();
        $this->projectStatus = ProjectStatusSetting::where('status', 'active')->get();

        $this->employees = '';

        if (($this->editPermission == 'all' || $this->editPermission = 'owned') || $this->editProjectMembersPermission == 'all') {
            $this->employees = User::allEmployees(null, null, ($this->editPermission == 'all' ? 'all' : null));
        }

        $userData = [];

        $usersData = $this->employees;

        foreach ($usersData as $user) {

            $url = route('employees.show', [$user->id]);

            $userData[] = ['id' => $user->id, 'value' => $user->name, 'image' => $user->image_url, 'link' => $url];

        }

        $this->userData = $userData;

        $this->view = 'projects.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        abort_403(user()->permission('edit_projects') == 'added' && $this->project->added_by != user()->id);

        return view('projects.create', $this->data);

    }

    /**
     * @param UpdateProject $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(UpdateProject $request, $id)
    {
        $project = Project::findOrFail($id);
        $project->project_name = $request->project_name;
        $project->project_short_code = $request->project_code;

        $project->project_summary = trim_editor($request->project_summary);

        $project->start_date = companyToYmd($request->start_date);

        if (!$request->has('without_deadline')) {
            $project->deadline = companyToYmd($request->deadline);
        }
        else {
            $project->deadline = null;
        }

        if ($request->notes != '') {
            $project->notes = trim_editor($request->notes);
        }

        if ($request->category_id != '') {
            $project->category_id = $request->category_id;
        }

        if ($request->client_view_task) {
            $project->client_view_task = 'enable';
        }
        else {
            $project->client_view_task = 'disable';
        }

        if ($request->client_task_notification) {
            $project->allow_client_notification = 'enable';
        }
        else {
            $project->allow_client_notification = 'disable';
        }

        if ($request->manual_timelog) {
            $project->manual_timelog = 'enable';
        }
        else {
            $project->manual_timelog = 'disable';
        }

        $project->team_id = null;

        if ($request->team_id > 0) {
            $project->team_id = $request->team_id;
        }

        $project->client_id = ($request->client_id == 'null' || $request->client_id == '') ? null : $request->client_id;

        if ($request->calculate_task_progress) {
            $project->calculate_task_progress = 'true';
            $project->completion_percent = $this->calculateProjectProgress($id, 'true');
        }
        else {
            $project->calculate_task_progress = 'false';
            $project->completion_percent = $request->completion_percent;
        }

        $project->project_budget = $request->project_budget;
        $project->currency_id = $request->currency_id != '' ? $request->currency_id : company()->currency_id;
        $project->hours_allocated = $request->hours_allocated;
        $project->status = $request->status;

        $project->miro_board_id = $request->miro_board_id;

        if ($request->has('miroboard_checkbox')) {
            $project->client_access = $request->has('client_access') && $request->client_access ? 1 : 0;
        }
        else {
            $project->client_access = 0;
        }

        $project->enable_miroboard = $request->has('miroboard_checkbox') && $request->miroboard_checkbox ? 1 : 0;

        if ($request->public) {
            $project->public = 1;
        }

        if ($request->private) {
            $project->public = 0;
        }


        if (!$request->private && !$request->public && $request->member_id) {
            $project->projectMembers()->sync($request->member_id);
        }

        $project->save();


        // // To add custom fields data
        // if ($request->custom_fields_data) {
        //     $project->updateCustomFieldData($request->custom_fields_data);
        // }

        $this->logProjectActivity($project->id, 'messages.updateSuccess');

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            // $redirectUrl = route('projects.index');
                    $redirectUrl = route('projects.show', $project->id);

        }

        return Reply::successWithData(__('messages.updateSuccess'), ['projectID' => $project->id, 'redirectUrl' => $redirectUrl]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
 public function show($id)
    {

        $this->viewPermission = user()->permission('view_projects');
        $viewFilePermission = user()->permission('view_project_files');
        $this->viewMiroboardPermission = user()->permission('view_miroboard');
        $viewMilestonePermission = user()->permission('view_project_milestones');
        $this->viewPaymentPermission = user()->permission('view_project_payments');
        $this->viewProjectTimelogPermission = user()->permission('view_project_timelogs');
        $this->viewExpensePermission = user()->permission('view_project_expenses');
        $this->viewRatingPermission = user()->permission('view_project_rating');
        $this->viewBurndownChartPermission = user()->permission('view_project_burndown_chart');
        $this->viewProjectMemberPermission = user()->permission('view_project_members');

        $this->project = Project::with(['client', 'members', 'members.user','mentionProject', 'members.user.session', 'members.user.employeeDetail.designation', 'milestones' => function ($q) use ($viewMilestonePermission) {
            if ($viewMilestonePermission == 'added') {
                $q->where('added_by', user()->id);
            }
        },
            'milestones.currency', 'files' => function ($q) use ($viewFilePermission) {
                if ($viewFilePermission == 'added') {
                    $q->where('added_by', user()->id);
                }
            }])
            ->withTrashed()
            ->findOrFail($id)
            ->withCustomFields();

        $this->projectStatusColor = ProjectStatusSetting::where('status_name', $this->project->status)->first();
        $memberIds = $this->project->members->pluck('user_id')->toArray();
        $mentionIds = $this->project->mentionProject->pluck('user_id')->toArray();

        abort_403(!(
            $this->viewPermission == 'all'
            || $this->project->public
            || ($this->viewPermission == 'added' && user()->id == $this->project->added_by)
            || ($this->viewPermission == 'owned' && user()->id == $this->project->client_id && in_array('client', user_roles()))
            || ($this->viewPermission == 'owned' && in_array(user()->id, $memberIds) && in_array('employee', user_roles()))
            || ($this->viewPermission == 'both' && (user()->id == $this->project->client_id || user()->id == $this->project->added_by))
            || ($this->viewPermission == 'both' && (in_array(user()->id, $memberIds) || user()->id == $this->project->added_by) && in_array('employee', user_roles()))
           || (($this->viewPermission == 'none') && (!is_null(($this->project->mentionProject))) && in_array(user()->id, $mentionIds))
        ));


        $this->pageTitle = $this->project->project_name;

        $getCustomFieldGroupsWithFields = $this->project->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->messageSetting = MessageSetting::first();
        $this->projectStatus = ProjectStatusSetting::where('status', 'active')->get();

        $tab = request('tab');

        switch ($tab) {
        case 'members':
            abort_403(!(
                $this->viewProjectMemberPermission == 'all'
            ));
            $this->view = 'projects.ajax.members';
            break;
        case 'milestones':
            $this->view = 'projects.ajax.milestones';
            break;
        // case 'taskboard':
        //     session()->forget('pusher_settings');
        //     $this->view = 'projects.ajax.taskboard';
        //     break;
        case 'sub-project':
            $this->taskBoardStatus = TaskboardColumn::all();

            return (!$this->project->trashed()) ? $this->tasks($this->project->project_admin == user()->id) : $this->archivedTasks($this->project->project_admin == user()->id);
        // case 'gantt':
        //     $this->taskBoardStatus = TaskboardColumn::all();
        //     $this->view = 'projects.ajax.gantt';
        //     break;

        case 'budgetHead':
            $this->view = 'projects.ajax.budgetHead';
            break;



            case 'budgetHeadList':
                $this->project_id = $id;
                return $this->budgetHeadList($this->project_id);
                break;

            case 'purchaseOrder':
                $this->project_id = $id;
                return $this->purchaseOrder($this->project_id);
                break;

            case 'purchaseBillList':
                $this->project_id = $id;
                return $this->purchaseBillList($this->project_id);
                break;

        case 'invoices':
            return $this->invoices();
        case 'files':
            $this->view = 'projects.ajax.files';
            break;
        case 'timelogs':
            return $this->timelogs($this->project->project_admin == user()->id);
        case 'expenses':
            return $this->expenses();
        case 'miroboard';
            abort_403(!in_array($this->viewMiroboardPermission, ['all']) || !$this->project->enable_miroboard &&
                ((!in_array('client', user_roles()) && !$this->project->client_access && $this->project->client_id != user()->id)));
            $this->view = 'projects.ajax.miroboard';
            break;
        case 'payments':
            return $this->payments();
        case 'discussion':
            $this->discussionCategories = DiscussionCategory::orderBy('order', 'asc')->get();

            return $this->discussions($this->project->project_admin == user()->id);
        case 'notes':
            return $this->notes($this->project->project_admin == user()->id);
        case 'rating':
            return $this->rating($this->project->project_admin == user()->id);
        case 'burndown-chart':
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            return $this->burndownChart($this->project);
        // case 'activity':
        //     $this->activities = [];

        //     if(!in_array('client', user_roles())){
        //         $this->activities = ProjectActivity::getProjectActivities($id, 10);
        //     }

        //     $this->view = 'projects.ajax.activity';
        //     break;
        case 'tickets':
            return $this->tickets($this->project->project_admin == user()->id);
        case 'orders':
            return $this->orders();
        default:
            $this->taskChart = $this->taskChartData($id);
            $hoursLogged = $this->project->times()->sum('total_minutes');

            $breakMinutes = ProjectTimeLogBreak::projectBreakMinutes($id);

            $this->hoursBudgetChart = $this->hoursBudgetChartData($this->project, $hoursLogged, $breakMinutes);

            $this->amountBudgetChart = $this->amountBudgetChartData($this->project);
            $this->taskBoardStatus = TaskboardColumn::all();
            $this->earnings = Payment::where('status', 'complete')
                ->where('project_id', $id)
                ->sum('amount');

            $this->hoursLogged = intdiv($hoursLogged - $breakMinutes, 60).'.'.(($hoursLogged - $breakMinutes) % 60);
            $this->expenses = Expense::where(['project_id' => $id, 'status' => 'approved'])->sum('price');
            $this->profit = $this->earnings - $this->expenses;

            $this->view = 'projects.ajax.overview';
            break;
        }



        $budgetData = DB::table('budget_head as bh')
                ->leftJoin('budget_head_details as bhd', 'bhd.budget_head_id', '=', 'bh.id')
                ->leftJoin('budget_head_name as bn', 'bn.id', '=', 'bhd.budget_head')
                ->selectRaw('SUBSTRING_INDEX(bhd.wbs, "-", -1) AS wbs_suffix, bn.name, bn.type, SUM(bhd.budget_cost) AS total_budget_cost, bh.project_id')
                ->where('bh.project_id', $id)
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->where('bh.status', 2)
                          ->where('bh.validate', 2)
                          ->where('bhd.status', 2)
                          ->where('bhd.val', 2);
                    })->orWhere(function($q) {
                        $q->where('bh.status', 1)
                          ->where('bh.validate', 2)
                          ->where('bhd.status', 2)
                          ->where('bhd.val', 2);
                    })->orWhere(function($q) {
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
                    ->where('po.project_id', $id)
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
                    ->where('po.project_id', $id)
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




        // Merge results
        $final = [];

        $total_budget_cost = 0;
        $total_committed_cost = 0;
        $total_actual_cost = 0;

        foreach ($budgetData as $budgetItem) {
            $wbs_suffix = $budgetItem->wbs_suffix;
            $project_id = $budgetItem->project_id;
            $committed = $committedData->firstWhere('wbs_suffix', $wbs_suffix);
            $actual = $actualData->firstWhere('wbs_suffix', $wbs_suffix);

            $committed_amount = $committed->total_amount ?? 0;
            $committed_gst = $committed->total_gst_amount ?? 0;
            $committed_cost = $committed_amount + $committed_gst;

            $actual_amount = $actual->total_amount ?? 0;
            $actual_tax = $actual->total_tax_amount ?? 0;
            $actual_cost = $actual_amount + $actual_tax;

            // Running totals
            $total_budget_cost += $budgetItem->total_budget_cost;
            $total_committed_cost += $committed_cost;
            $total_actual_cost += $actual_cost;

            $final[] = [
                'wbs_suffix' => $wbs_suffix,
                 'project_id' => $project_id,
                'name' => $budgetItem->name,
                'type' => $budgetItem->type,
                'total_budget_cost' => $budgetItem->total_budget_cost,
                'committed_cost' => $committed_cost,
                'actual_cost' => $actual_cost,
            ];
        }

        // Final totals
        $totals = [
            'total_budget_cost' => $total_budget_cost,
            'total_committed_cost' => $total_committed_cost,
            'total_actual_cost' => $total_actual_cost,
        ];

        $this->total_costing = $totals;

        $buildItems = array_filter($final, fn($item) => $item['type'] === 'build');
        $devTechItems = array_filter($final, fn($item) => in_array($item['type'], ['dev', 'tech']));

        $this->committedVsActualChart = $this->committedVsActualChart();
        $this->committedVsActualBySubProjectChart = $this->committedVsActualBySubProjectChart();
        $this->budgetHeadChartData = $this->budgetHeadChart();
        $this->totalWBSGraph= $this->totalWBSGraph($final);
        $this->totalGraph = $this->totalGraph($totals);
        $this->totalWBSData = $final;
        $this->graphBuild = $this->totalWBSGraph($buildItems);
        $this->graphDevTech = $this->totalWBSGraph($devTechItems);
        $this->graphBuildTotal = $this->totalGraph($this->calculateTotals($buildItems));
        $this->graphDevTechTotal = $this->totalGraph($this->calculateTotals($devTechItems));


        // echo '<pre>';print_r($final); exit;
        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('projects.show', $this->data);

    }

    public function calculateTotals($items)
    {
        $budget = 0;
        $committed = 0;
        $actual = 0;

        foreach ($items as $item) {
            $budget += $item['total_budget_cost'];
            $committed += $item['committed_cost'];
            $actual += $item['actual_cost'];
        }

        return [
            'total_budget_cost' => $budget,
            'total_committed_cost' => $committed,
            'total_actual_cost' => $actual,
        ];
    }


    public function totalWBSGraph($final)
    {
        $labels = collect();
        $budgetData = collect();
        $poData = collect();
        $actualData = collect();

        foreach ($final as $item) {
            $labels->push($item['name']);
            $budgetData->push(round(($item['total_budget_cost'] ?? 0) / 10000000, 2)); // Cr
            $poData->push(round(($item['committed_cost'] ?? 0) / 10000000, 2));        // Cr
            $actualData->push(round(($item['actual_cost'] ?? 0) / 10000000, 2));       // Cr
        }

        return [
            'labels' => $labels,
            'values' => [$budgetData, $poData, $actualData],
            'colors' => ['#007bff', '#ffc107', '#28a745'],
            'name' => ['Budget', 'Committed (PO)', 'Actual'],
            'tooltipLabels' => $labels->map(function ($label, $i) use ($budgetData, $poData, $actualData) {
                return [
                    'budget' => 'Budget: ₹ ' . number_format($budgetData[$i], 2) . ' Cr',
                    'committed' => 'Committed: ₹ ' . number_format($poData[$i], 2) . ' Cr',
                    'actual' => 'Actual: ₹ ' . number_format($actualData[$i], 2) . ' Cr',
                ];
            }),
        ];
    }

    public function totalGraph($totals)
    {
        return [
            'labels' => ['Budget', 'Committed (PO)', 'Actual'],
            'values' => [
                collect([round(($totals['total_budget_cost'] ?? 0) / 10000000, 2)]),
                collect([round(($totals['total_committed_cost'] ?? 0) / 10000000, 2)]),
                collect([round(($totals['total_actual_cost'] ?? 0) / 10000000, 2)]),
            ],
            'colors' => ['#007bff', '#ffc107', '#28a745'],
            'name' => ['Budget', 'Committed (PO)', 'Actual'],
            'tooltipLabels' => collect([
                'Budget: ₹ ' . number_format(($totals['total_budget_cost'] ?? 0) / 10000000, 2) . ' Cr',
                'Committed: ₹ ' . number_format(($totals['total_committed_cost'] ?? 0) / 10000000, 2) . ' Cr',
                'Actual: ₹ ' . number_format(($totals['total_actual_cost'] ?? 0) / 10000000, 2) . ' Cr',
            ]),
        ];
    }
     public function committedVsActualChart()
    {
        $period = now()->subMonths(11)->monthsUntil(now());

        $startDate = $period->startDate->startOfMonth();
        $endDate = $period->endDate->endOfMonth();

        $months = [];

        foreach ($period as $periodData) {
            $months[$periodData->format('m-Y')] = [
                'y' => $periodData->translatedFormat('F'),
                'committed' => 0,
                'actual' => 0
            ];
        }

        // Static Actual Data (amounts in rupees)
        $actualData = [
            '01-2024' => 9500000, '02-2024' => 11250000, '03-2024' => 8700000,
            '04-2024' => 9900000, '05-2024' => 10800000, '06-2024' => 10050000,
            '07-2024' => 9700000, '08-2024' => 10700000, '09-2024' => 9600000,
            '10-2024' => 10200000, '11-2024' => 10450000, '12-2024' => 9950000,
        ];

        // Static Committed Data (amounts in rupees)
        $committedData = [
            '01-2024' => 10000000, '02-2024' => 11000000, '03-2024' => 9000000,
            '04-2024' => 9500000, '05-2024' => 10500000, '06-2024' => 10200000,
            '07-2024' => 9900000, '08-2024' => 10300000, '09-2024' => 9800000,
            '10-2024' => 9700000, '11-2024' => 10100000, '12-2024' => 10000000,
        ];

        $graphData = [];

        foreach ($months as $key => $month) {
            $actual = $actualData[$key] ?? 0;
            $committed = $committedData[$key] ?? 0;

            $graphData[] = [
                'y' => $month['y'],
                'committed' => round($committed / 10000000, 2), // value in crores
                'actual' => round($actual / 10000000, 2)        // value in crores
            ];
        }

        $graphData = collect($graphData);

        $data['labels'] = $graphData->pluck('y');
        $data['values'][] = $graphData->pluck('committed');
        $data['values'][] = $graphData->pluck('actual');
        $data['colors'] = ['#6f42c1', '#28a745'];
        $data['name'][] = 'Committed (Cr)';
        $data['name'][] = 'Actual (Cr)';

        return $data;
    }


    public function committedVsActualBySubProjectChart()
    {
        $period = now()->subMonths(11)->monthsUntil(now());

        $startDate = $period->startDate->startOfMonth();
        $endDate = $period->endDate->endOfMonth();

        $months = [];

        foreach ($period as $periodData) {
            $months[$periodData->format('m-Y')] = [
                'y' => $periodData->translatedFormat('F'),
                'committed' => 0,
                'actual' => 0
            ];
        }

        // Static Actual Data (amounts in rupees)
        $actualData = [
            '01-2024' => 9500000, '02-2024' => 11250000, '03-2024' => 8700000,
            '04-2024' => 9900000, '05-2024' => 10800000, '06-2024' => 10050000,
            '07-2024' => 9700000, '08-2024' => 10700000, '09-2024' => 9600000,
            '10-2024' => 10200000, '11-2024' => 10450000, '12-2024' => 9950000,
        ];

        // Static Committed Data (amounts in rupees)
        $committedData = [
            '01-2024' => 10000000, '02-2024' => 11000000, '03-2024' => 9000000,
            '04-2024' => 9500000, '05-2024' => 10500000, '06-2024' => 10200000,
            '07-2024' => 9900000, '08-2024' => 10300000, '09-2024' => 9800000,
            '10-2024' => 9700000, '11-2024' => 10100000, '12-2024' => 10000000,
        ];

        $graphData = [];

        foreach ($months as $key => $month) {
            $actual = $actualData[$key] ?? 0;
            $committed = $committedData[$key] ?? 0;

            $graphData[] = [
                'y' => $month['y'],
                'committed' => round($committed / 10000000, 2), // value in crores
                'actual' => round($actual / 10000000, 2)        // value in crores
            ];
        }

        $graphData = collect($graphData);

        $data['labels'] = $graphData->pluck('y');
        $data['values'][] = $graphData->pluck('committed');
        $data['values'][] = $graphData->pluck('actual');
        $data['colors'] = ['#6f42c1', '#28a745'];
        $data['name'][] = 'Committed (Cr)';
        $data['name'][] = 'Actual (Cr)';

        return $data;
}


public function budgetHeadChart()
{
    // Simulated data – replace with your DB logic
    $budgetHeads = [
        'Salaries' => ['committed' => 120000000, 'actual' => 100000000],
        'Infrastructure' => ['committed' => 150000000, 'actual' => 130000],
        'Training' => ['committed' => 8000000, 'actual' => 6000000],
        'Marketing' => ['committed' => 20000000, 'actual' => 18000000],
        'Miscellaneous' => ['committed' => 5000000, 'actual' => 3000000],
        'Travel' => ['committed' => 7000000, 'actual' => 5000000],
        'Equipment' => ['committed' => 9000000, 'actual' => 7000000],
        'Consulting' => ['committed' => 11000000, 'actual' => 9000000],
        'Office Supplies' => ['committed' => 6000000, 'actual' => 4000000],
        'Utilities' => ['committed' => 4000000, 'actual' => 3000000],
        'Insurance' => ['committed' => 3000000, 'actual' => 2000000],
    ];

    $labels = array_keys($budgetHeads);
    $committed = [];
    $actual = [];
    $remaining = [];

    foreach ($budgetHeads as $head => $amounts) {
        $committed[] = round($amounts['committed'] / 10000000, 2); // In Cr
        $actual[] = round($amounts['actual'] / 10000000, 2);       // In Cr
        $remaining[] = round(($amounts['committed'] - $amounts['actual']) / 10000000, 2); // In Cr
    }

    return [
        'labels' => $labels,
        'values' => [
            $committed,
            $actual,
            $remaining,
        ],
        'colors' => ['#007bff', '#28a745', '#ffc107'],
        'name' => ['Committed (Cr)', 'Actual (Cr)', 'Remaining (Cr)']
    ];
}

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function taskChartData($id)
    {
        $taskStatus = TaskboardColumn::all();
        $data['labels'] = $taskStatus->pluck('column_name');
        $data['colors'] = $taskStatus->pluck('label_color');
        $data['values'] = [];

        foreach ($taskStatus as $label) {
            $data['values'][] = Task::where('project_id', $id)->where('tasks.board_column_id', $label->id)->count();
        }

        return $data;
    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function hoursBudgetChartData($project, $hoursLogged, $breakMinutes)
    {
        $hoursBudget = $project->hours_allocated ? $project->hours_allocated : 0;

        $hoursLogged = intdiv($hoursLogged - $breakMinutes, 60);
        $overRun = $hoursLogged - $hoursBudget;
        $overRun = $overRun < 0 ? 0 : $overRun;
        $hoursLogged = ($hoursLogged > $hoursBudget) ? $hoursBudget : $hoursLogged;

        $data['labels'] = [__('app.planned'), __('app.actual')];
        $data['colors'] = ['#2cb100', '#d30000'];
        $data['threshold'] = $hoursBudget;
        $dataset = [
            [
                'name' => __('app.planned'),
                'values' => [$hoursBudget, $hoursLogged],
            ],
            [
                'name' => __('app.overrun'),
                'values' => [0, $overRun],
            ],
        ];
        $data['datasets'] = $dataset;
        return $data;
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function amountBudgetChartData($project)
    {
        $amountBudget = $project->project_budget ?: 0;
        $earnings = Payment::where('status', 'complete')
            ->where('project_id', $project->id)
            ->sum('amount');
        $plannedOverun = $earnings < $amountBudget ? $earnings : $amountBudget;
        $overRun = $earnings - $amountBudget;
        $overRun = $overRun < 0 ? 0 : $overRun;

        $data['labels'] = [__('app.planned'), __('app.actual')];
        $data['colors'] = ['#2cb100', '#d30000'];
        $data['threshold'] = $amountBudget;
        $dataset = [
            [
                'name' => __('app.planned'),
                'values' => [$amountBudget, $plannedOverun],
            ],
            [
                'name' => __('app.overrun'),
                'values' => [0, $overRun],
            ],
        ];
        $data['datasets'] = $dataset;

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function storePin(Request $request)
    {
        $pinned = new Pinned();
        $pinned->task_id = $request->task_id;
        $pinned->project_id = $request->project_id;
        $pinned->save();

        return Reply::success(__('messages.pinnedSuccess'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return array
     */
    public function destroyPin(Request $request, $id)
    {
        Pinned::where('project_id', $id)->where('user_id', user()->id)->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function assignProjectAdmin(Request $request)
    {
        $userId = $request->userId;
        $projectId = $request->projectId;
        $project = Project::findOrFail($projectId);
        $project->project_admin = $userId;
        $project->save();

        return Reply::success(__('messages.roleAssigned'));
    }

    public function tasks($projectAdmin = false)
    {
        $dataTable = new TasksDataTable(true);

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_tasks');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

            $viewPermission = user()->permission('view_tasks');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.tasks';

        return $dataTable->render('projects.show', $this->data);

    }

    public function archivedTasks($projectAdmin = false)
    {
        $dataTable = new ArchiveTasksDataTable();

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_tasks');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

            $viewPermission = user()->permission('view_tasks');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.tasks';

        return $dataTable->render('projects.show', $this->data);

    }

    public function ganttData()
    {
        $id = request('projectID');
        $assignedTo = request('assignedTo');
        $projectTask = request('projectTask');
        $taskStatus = request('taskStatus');
        $milestones = request('milestones');
        $withoutDueDate = false;

        if ($assignedTo != 'all') {
            $tasks = Task::projectTasks($id, $assignedTo, null, $withoutDueDate);
        }
        else {
            $tasks = Task::projectTasks($id, null, null, $withoutDueDate);
        }

        if ($projectTask) {
            $tasks = $tasks->whereIn('id', explode(',', $projectTask));
        }

        if ($taskStatus) {
            $tasks = $tasks->whereIn('board_column_id', explode(',', $taskStatus));
        }

        if ($milestones != '') {
            $tasks = $tasks->whereIn('milestone_id', explode(',', $milestones));
        }

        $data = array();

        $count = 0;

        foreach ($tasks as $task) {
            $data[$count] = [
                'id' => 'task-' . $task->id,
                'name' => $task->heading,
                'start' => ((!is_null($task->start_date)) ? $task->start_date->format('Y-m-d') : ((!is_null($task->due_date)) ? $task->due_date->format('Y-m-d') : null)),
                'end' => (!is_null($task->due_date)) ? $task->due_date->format('Y-m-d') : $task->start_date->format('Y-m-d'),
                'progress' => 0,
                'bg_color' => $task->boardColumn->label_color,
                'taskid' => $task->id,
                'draggable' => true
            ];

            if (!is_null($task->dependent_task_id)) {
                $data[$count]['dependencies'] = 'task-' . $task->dependent_task_id;
            }

            $count++;
        }

        return response()->json($data);
    }

    public function invoices()
    {
        $dataTable = new InvoicesDataTable($this->onlyTrashedRecords);
        $viewPermission = user()->permission('view_project_invoices');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.invoices';

        return $dataTable->render('projects.show', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function invoiceList(Request $request, $id)
    {
        $options = '<option value="">--</option>';

        $viewPermission = user()->permission('view_invoices');

        if (($viewPermission == 'all' || $viewPermission == 'added')) {

            if ($id != 0) {
                $invoices = Invoice::with('payment', 'currency')->where('project_id', $id)->where('send_status', 1)->pending()->get();
            }
            else {
                $invoices = Invoice::with('payment')->where('send_status', 1)
                    ->where(function ($q) {
                        $q->where('status', 'unpaid')
                            ->orWhere('status', 'partial');
                    })->get();
            }

            foreach ($invoices as $item) {
                $paidAmount = $item->amountPaid();

                $options .= '<option data-currency-code="'.$item->currency->currency_code.'" data-currency-id="' . $item->currency_id . '" data-content="' . $item->invoice_number . ' - ' . __('app.total') . ': <span class=\'text-dark f-w-500 mr-2\'>' . currency_format($item->total, $item->currency->id) . ' </span>' . __('modules.invoices.due') . ': <span class=\'text-red\'>' . currency_format(max(($item->total - $paidAmount), 0), $item->currency->id) . '</span>" value="' . $item->id . '"> ' . $item->invoice_number . ' </option>';
            }

        }

        $bankData = '<option value="">--</option>';

        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankDetails = BankAccount::where('status', 1)->where('currency_id', $request->currencyId);

        if($this->viewBankAccountPermission == 'added'){
            $bankDetails = $bankDetails->where('added_by', user()->id);
        }

        $bankDetails = $bankDetails->get();

        foreach ($bankDetails as $bankDetail) {

            $bankName = '';

            if($bankDetail->type == 'bank')
            {
                $bankName = $bankDetail->bank_name.' |';
            }

            $bankData .= '<option value="' . $bankDetail->id . '">'.$bankName .' '.$bankDetail->account_name. '</option>';
        }

        $exchangeRate = Currency::where('id', $request->currencyId)->pluck('exchange_rate')->toArray();

        return Reply::dataOnly(['status' => 'success', 'data' => $options, 'account' => $bankData, 'exchangeRate' => $exchangeRate]);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function members($id)
    {
        $options = '';
        $userData = [];

        $project = Project::select('id', 'public')->find($id);
        $checkPublic = ($project) ? $project->public : 0;

        if ($id == 0 || $checkPublic == 1) {
            $members = User::allEmployees(null, false);

            foreach ($members as $item) {
                $self_select = (user() && user()->id == $item->id) ? '<span class=\'ml-2 badge badge-secondary\'>' . __('app.itsYou') . '</span>' : '';

                $options .= '<option  data-content="<span class=\'badge badge-pill badge-light border\'><div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=' . $item->image_url . ' ></div> ' . $item->name . '' . $self_select . '</span>" value="' . $item->id . '"> ' . $item->name . '</option>';
            }

            $projectShortCode = '--';
        }
        else {

            // $members = ProjectMember::with('user')->where('project_id', $id)->whereHas('user', function ($q) {
            //     $q->where('status', 'active');
            // })->get();

            $members = ProjectMember::with('user')->where('project_id', $id)->get();


            foreach ($members as $item) {
                $content = ( $item->user->status == 'deactive') ? "<span class='badge badge-pill badge-danger border align-center ml-2 px-2'>Inactive</span>" : '';
                $self_select = (user() && user()->id == $item->user->id) ? '<span class=\'ml-2 badge badge-secondary\'>' . __('app.itsYou') . '</span>' : '';

                $options .= '<option
                data-content="<span class=\'badge badge-pill badge-light border\'><div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=' . $item->user->image_url . ' ></div> ' . $item->user->name . '' . $self_select . '' . $content .'</span>"
                value="' . $item->user->id . '"> ' . $item->user->name . ' </option>';

                $url = route('employees.show', [$item->user->id]);

                $userData[] = ['id' => $item->user->id, 'value' => $item->user->name, 'image' => $item->user->image_url, 'link' => $url];
            }


            $project = Project::findOrFail($id);
            $projectShortCode = $project->project_short_code;

        }

        return Reply::dataOnly(['status' => 'success', 'unique_id' => $projectShortCode, 'data' => $options, 'userData' => $userData]);

    }

    public function timelogs($projectAdmin = false)
    {
        $dataTable = new TimeLogsDataTable($this->onlyTrashedRecords);

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_timelogs');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));
        }

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.timelogs';

        return $dataTable->render('projects.show', $this->data);
    }

    public function expenses()
    {
        $dataTable = new ExpensesDataTable($this->onlyTrashedRecords);
        $viewPermission = user()->permission('view_project_expenses');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.expenses';

        return $dataTable->render('projects.show', $this->data);

    }

    public function payments()
    {
        $dataTable = new PaymentsDataTable($this->onlyTrashedRecords);
        $viewPermission = user()->permission('view_project_payments');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.payments';

        return $dataTable->render('projects.show', $this->data);

    }

    public function discussions($projectAdmin = false)
    {
        $dataTable = new DiscussionDataTable();

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_discussions');
            abort_403(!in_array($viewPermission, ['all', 'added']));
        }

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.discussion';

        return $dataTable->render('projects.show', $this->data);

    }

    public function burndown(Request $request, $id)
    {
        $this->project = Project::with(['tasks' => function ($query) use ($request) {
            if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
                $query->where(DB::raw('DATE(`start_date`)'), '>=', Carbon::createFromFormat($this->company->date_format, $request->startDate));
            }

            if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
                $query->where(DB::raw('DATE(`due_date`)'), '<=', Carbon::createFromFormat($this->company->date_format, $request->endDate));
            }

            $query->whereNotNull('due_date');
        }])->withTrashed()->findOrFail($id);

        $this->totalTask = $this->project->tasks->count();
        $datesArray = [];
        $startDate = $request->startDate ? Carbon::createFromFormat($this->company->date_format, $request->startDate) : Carbon::parse($this->project->start_date);

        if ($this->project->deadline) {
            $endDate = $request->endDate ? Carbon::createFromFormat($this->company->date_format, $request->endDate) : Carbon::parse($this->project->deadline);
        }
        else {
            $endDate = $request->endDate ? Carbon::createFromFormat($this->company->date_format, $request->endDate) : now();
        }

        for ($startDate; $startDate <= $endDate; $startDate->addDay()) {
            $datesArray[] = $startDate->format($this->company->date_format);
        }

        $uncompletedTasks = [];
        $createdTasks = [];
        $deadlineTasks = [];
        $deadlineTasksCount = [];
        $this->datesArray = json_encode($datesArray);

        foreach ($datesArray as $key => $value) {

            if (Carbon::createFromFormat($this->company->date_format, $value)->lessThanOrEqualTo(now())) {
                $uncompletedTasks[$key] = $this->project->tasks->filter(function ($task) use ($value) {

                    if (is_null($task->completed_on)) {
                        return true;
                    }

                    return $task->completed_on ? $task->completed_on->greaterThanOrEqualTo(Carbon::createFromFormat($this->company->date_format, $value)) : false;
                })->count();

                $createdTasks[$key] = $this->project->tasks->filter(function ($task) use ($value) {
                    return Carbon::createFromFormat($this->company->date_format, $value)->startOfDay()->equalTo($task->created_at->startOfDay());
                })->count();

                if ($key > 0) {
                    $uncompletedTasks[$key] += $createdTasks[$key];
                }

            }

            $deadlineTasksCount[] = $this->project->tasks->filter(function ($task) use ($value) {
                return Carbon::createFromFormat($this->company->date_format, $value)->startOfDay()->equalTo($task->due_date->startOfDay());
            })->count();

            if ($key == 0) {
                $deadlineTasks[$key] = $this->totalTask - $deadlineTasksCount[$key];
            }
            else {
                $newKey = $key - 1;
                $deadlineTasks[$key] = $deadlineTasks[$newKey] - $deadlineTasksCount[$key];
            }
        }

        $this->uncompletedTasks = json_encode($uncompletedTasks);
        $this->deadlineTasks = json_encode($deadlineTasks);

        if ($request->ajax()) {
            return $this->data;
        }

        $this->startDate = $request->startDate ? Carbon::parse($request->startDate)->format($this->company->date_format) : Carbon::parse($this->project->start_date)->format($this->company->date_format);
        $this->endDate = $endDate->format($this->company->date_format);

        return view('projects.ajax.burndown', $this->data);
    }

    public function notes($projectAdmin = false)
    {
        $dataTable = new ProjectNotesDataTable();

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_note');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';

        $this->view = 'projects.ajax.notes';

        return $dataTable->render('projects.show', $this->data);

    }

    public function tickets($projectAdmin = false)
    {
        $dataTable = new TicketDataTable($this->onlyTrashedRecords);

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_tickets');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $this->activeTab = request()->tab ?: 'profile';
        $this->view = 'projects.ajax.tickets';

        return $dataTable->render('projects.show', $this->data);

    }

    public function burndownChart($project)
    {
        $viewPermission = user()->permission('view_project_burndown_chart');
        abort_403(!(in_array($viewPermission, ['all']) || $project->project_admin == user()->id));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'burndown-chart';
        $this->view = 'projects.ajax.burndown';

        return view('projects.show', $this->data);

    }

    public function rating($projectAdmin)
    {

        if (!$projectAdmin) {
            $viewPermission = user()->permission('view_project_rating');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $this->deleteRatingPermission = user()->permission('delete_project_rating');
        $this->editRatingPermission = user()->permission('edit_project_rating');
        $this->addRatingPermission = user()->permission('add_project_rating');


        $tab = request('tab');
        $this->activeTab = $tab ?: 'rating';


        $this->view = 'projects.ajax.rating';

        return view('projects.show', $this->data);

    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function archive(ArchiveProjectsDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_projects');
        abort_403($viewPermission == 'none');

        if (!request()->ajax()) {

            if (in_array('client', user_roles())) {
                $this->clients = User::client();
            }
            else {
                $this->clients = User::allClients();
                $this->allEmployees = User::allEmployees();
            }

            $this->categories = ProjectCategory::all();
            $this->departments = Team::all();
        }

        return $dataTable->render('projects.archive', $this->data);

    }

    public function archiveRestore($id)
    {
        $project = Project::withTrashed()->findOrFail($id);
        $project->restore();

        return Reply::success(__('messages.projectRevertSuccessfully'));
    }

    public function importProject()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.menu.projects');

        $this->addPermission = user()->permission('add_projects');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->view = 'projects.ajax.import';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('projects.create', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $this->importFileProcess($request, ProjectImport::class);

        $view = view('projects.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, ProjectImport::class, ImportProjectJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function changeProjectStatus(Request $request)
    {
        $projectId = $request->projectId;
        $statusID = $request->statusId;
        $project = Project::with('members')->findOrFail($projectId);
        $projectUsers = $project->members->pluck('user_id')->toArray();

        $this->editProjectPermission = user()->permission('edit_projects');

        abort_403(!(
            $this->editProjectPermission == 'all'
            || ($this->editProjectPermission == 'added' && user()->id == $project->added_by)
            || ($this->editProjectPermission == 'owned' && user()->id == $project->client_id && in_array('client', user_roles()))
            || ($this->editProjectPermission == 'owned' && in_array(user()->id, $projectUsers) && in_array('employee', user_roles()))
            || ($this->editProjectPermission == 'both' && (user()->id == $project->client_id || user()->id == $project->added_by))
            || ($this->editProjectPermission == 'both' && in_array(user()->id, $projectUsers) && in_array('employee', user_roles())
            )));

        $projectStatus = ProjectStatusSetting::where('status_name', $statusID)->first();

        $project->status = $projectStatus->status_name;
        $project->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function pendingTasks($id)
    {
        if ($id != 0) {
            $tasks = Task::join('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->with('project')
                ->pending()
                ->where('task_users.user_id', '=', $this->user->id)
                ->where('tasks.project_id', '=', $id)
                ->select('tasks.*')
                ->get();

        }
        else {
            $tasks = Task::join('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->with('project')
                ->pending()
                ->where('task_users.user_id', '=', $this->user->id)
                ->select('tasks.*')
                ->get();
        }

        $options = '<option value="">--</option>';

        foreach ($tasks as $item) {
            $name = '';

            if (!is_null($item->project_id)) {
                $name .= "<h5 class='f-12 text-darkest-grey'>" . $item->heading . "</h5><div class='text-muted f-11'>" . $item->project->project_name . '</div>';

            }
            else {
                $name .= "<span class='text-dark-grey f-11'>" . $item->heading . '</span>';
            }

            $options .= '<option data-content="' . $name . '" value="' . $item->id . '">' . $item->heading . '</option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);

    }

    public function ajaxLoadProject(Request $request)
    {
        $search = $request->search;

        $response = [];

        if ($search) {
            $lists = Project::allProjects($search);

            foreach ($lists as $list) {
                $response[] = [
                    'id' => $list->id,
                    'text' => $list->project_name,
                ];
            }
        }


        return response()->json($response);
    }

    public function duplicateProject($id)
    {
        $this->projectId = $id;

        $this->project = Project::findOrFail($id);

        $this->taskboardColumns = TaskboardColumn::orderBy('priority', 'asc')->get();

        $addPermission = user()->permission('add_projects');
        $this->memberIds = $this->project->members->pluck('user_id')->toArray();

        $this->employees = User::allEmployees(null, false, ($addPermission == 'all' ? 'all' : null));

        $this->clients = User::allClients(null, false, ($addPermission == 'all' ? 'all' : null));

        if (in_array('client', user_roles())) {
            $this->client = User::withoutGlobalScope(ActiveScope::class)->findOrFail(user()->id);

        }
        else {
            $this->client = isset(request()->default_client) ? User::withoutGlobalScope(ActiveScope::class)->findOrFail(request()->default_client) : null;
        }

        return view('projects.duplicate-project', $this->data);
    }

    public function storeDuplicateProject($request, $project)
    {
        // For duplicate project
        if($request->has('file')){

            $projectExists = ProjectFile::where('project_id', $request->duplicateProjectID)->get();

            if ($projectExists) {
                foreach ($projectExists as $projectExist) {
                    $file = new ProjectFile();
                    $file->user_id = $projectExist->user_id;
                    $file->project_id = $project->id;

                    $fileName = Files::generateNewFileName($projectExist->filename);

                    Files::copy(ProjectFile::FILE_PATH . '/' . $projectExist->project_id . '/' . $projectExist->hashname, ProjectFile::FILE_PATH . '/' . $project->id . '/' . $fileName);

                    $file->filename = $projectExist->filename;
                    $file->hashname = $fileName;
                    $file->size = $projectExist->size;
                    $file->save();

                    $this->logProjectActivity($project->id, $this->user->id, 'fileActivity', $project->board_column_id); /* @phpstan-ignore-line */
                }
            }

        }

        if($request->has('milestone')){

            $projectMilestoneExists = ProjectMilestone::where('project_id', $request->duplicateProjectID)->get();

            if ($projectMilestoneExists) {

                foreach ($projectMilestoneExists as $projectMilestoneExist) {
                    $milestone = new ProjectMilestone();
                    $milestone->project_id = $project->id;
                    $milestone->milestone_title = $projectMilestoneExist->milestone_title;
                    $milestone->summary = $projectMilestoneExist->summary;
                    $milestone->cost = $projectMilestoneExist->cost;
                    $milestone->currency_id = $projectMilestoneExist->currency_id;
                    $milestone->status = $projectMilestoneExist->status;
                    $milestone->start_date = $projectMilestoneExist->start_date;
                    $milestone->end_date = $projectMilestoneExist->end_date;
                    $milestone->save();

                    $this->logProjectActivity($milestone->project_id, 'messages.milestoneUpdated');
                }
            }

        }

        if($request->has('time_sheet')){

            $projectTimeLogExists = ProjectTimeLog::where('project_id', $request->duplicateProjectID)->get();

            if ($projectTimeLogExists) {

                foreach ($projectTimeLogExists as $projectTimeLogExist) {
                    $projectTimeLog = new ProjectTimeLog();
                    $projectTimeLog->project_id = $project->id;
                    $projectTimeLog->task_id = $projectTimeLogExist->task_id;
                    $projectTimeLog->user_id = $projectTimeLogExist->user_id;
                    $projectTimeLog->start_time = $projectTimeLogExist->start_time;
                    $projectTimeLog->end_time = $projectTimeLogExist->end_time;
                    $projectTimeLog->total_hours = $projectTimeLogExist->total_hours;
                    $projectTimeLog->total_minutes = $projectTimeLogExist->total_minutes;
                    $projectTimeLog->hourly_rate = $projectTimeLogExist->hourly_rate;
                    $projectTimeLog->memo = $projectTimeLogExist->memo;
                    $projectTimeLog->edited_by_user = user()->id;
                    $projectTimeLog->save();
                }
            }

        }

        if($request->has('note')){

            $projectNoteExists = ProjectNote::where('project_id', $request->duplicateProjectID)->get();

            if ($projectNoteExists) {

                foreach ($projectNoteExists as $projectNoteExist) {
                    $projectNote = new ProjectNote();
                    $projectNote->project_id = $project->id;
                    $projectNote->title = $projectNoteExist->title;
                    $projectNote->details = $projectNoteExist->details;
                    $projectNote->type = $projectNoteExist->type;
                    $projectNote->client_id = $projectNoteExist->client_id;
                    $projectNote->is_client_show = $projectNoteExist->is_client_show;
                    $projectNote->ask_password = $projectNoteExist->ask_password;
                    $projectNote->save();
                }
            }

        }

        if($request->has('task')){

            $projectTasks = Task::with('labels', 'taskUsers');

            if($request->task_status){
                $projectTasks->whereIn('board_column_id', $request->task_status);
            }

            $projectTasks = $projectTasks->where('project_id', $request->duplicateProjectID)->get();
            $taskBoard = TaskboardColumn::where('slug', 'incomplete')->first();

            if ($projectTasks) {

                foreach ($projectTasks as $projectTask) {

                    $task = new Task();
                    $task->company_id = company()->id;
                    $task->project_id = $project->id;
                    $task->heading = $projectTask->heading;
                    $task->description = trim_editor($projectTask->description);
                    $task->start_date = $projectTask->start_date;
                    $task->due_date = $projectTask->due_date;
                    $task->task_category_id = $projectTask->category_id; /* @phpstan-ignore-line */
                    $task->priority = $projectTask->priority;
                    $task->board_column_id = $taskBoard->id;
                    $task->dependent_task_id = $projectTask->dependent_task_id;
                    $task->is_private = $projectTask->is_private;
                    $task->billable = $projectTask->billable;
                    $task->estimate_hours = $projectTask->estimate_hours;
                    $task->estimate_minutes = $projectTask->estimate_minutes;
                    $task->milestone_id = $projectTask->milestone_id;
                    $task->repeat = $projectTask->repeat;
                    $task->hash = md5(microtime());

                    if ($projectTask->repeat) {
                        $task->repeat_count = $projectTask->repeat_count;
                        $task->repeat_type = $projectTask->repeat_type;
                        $task->repeat_cycles = $projectTask->repeat_cycles;
                    }

                    if ($project) {
                        $projectLastTaskCount = Task::projectTaskCount($project->id);
                        $task->task_short_code = ($project) ? $project->project_short_code . '-' . ((int)$projectLastTaskCount + 1) : null;
                    }

                    $task->saveQuietly();

                    $this->saveSubTask($projectTask, $task, $request);
                }
            }
        }
    }

    public function saveSubTask($projectTask, $task, $request)
    {
        if($request->has('same_assignee')){
            foreach($projectTask->taskUsers as $taskUsers){
                $taskUser = new TaskUser();
                $taskUser->task_id = $task->id;
                $taskUser->user_id = $taskUsers->user_id;
                $taskUser->save();
            }
        }

        if (!is_null($projectTask->id) && $request->has('sub_task')) {

            $subTasks = SubTask::with(['files'])->where('task_id', $projectTask->id)->get();

            if ($subTasks) {
                foreach ($subTasks as $subTask) {
                    $subTaskData = new SubTask();
                    $subTaskData->title = $subTask->title;
                    $subTaskData->task_id = $task->id;
                    $subTaskData->description = trim_editor($subTask->description);

                    if ($subTask->start_date != '' && $subTask->due_date != '') {
                        $subTaskData->start_date = $subTask->start_date;
                        $subTaskData->due_date = $subTask->due_date;
                    }

                    $subTaskData->assigned_to = $subTask->assigned_to;

                    $subTaskData->save();

                    if ($subTask->files) {
                        foreach ($subTask->files as $fileData) {
                            $file = new SubTaskFile();
                            $file->user_id = $fileData->user_id;
                            $file->sub_task_id = $subTaskData->id;

                            $fileName = Files::generateNewFileName($fileData->filename);

                            Files::copy(SubTaskFile::FILE_PATH . '/' . $fileData->sub_task_id . '/' . $fileData->hashname, SubTaskFile::FILE_PATH . '/' . $subTaskData->id . '/' . $fileName);

                            $file->filename = $fileData->filename;
                            $file->hashname = $fileName;
                            $file->size = $fileData->size;
                            $file->save();
                        }
                    }
                }
            }
        }
    }

    public function getProjects(Request $request)
    {
        $projects = Project::query()
            ->when(($request->requesterType == 'client' && $request->clientId), function ($query) use ($request) {
                $query->where('client_id', $request->clientId);
            })
            ->when(($request->requesterType == 'employee' && $request->userId), function ($query) use ($request) {
                $query->whereHas('members', function ($q) use ($request) {
                    $q->where('user_id', $request->userId);
                });
            })
            ->get();

        return Reply::dataOnly(['projects' => $projects]);
    }

    public function orders()
    {
        $dataTable = new OrdersDataTable($this->onlyTrashedRecords);
        $viewPermission = user()->permission('view_project_orders');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';

        $this->view = 'projects.ajax.orders';

        return $dataTable->render('projects.show', $this->data);
    }
    public function getDepartmentData(Request $request)
    {
        $query = DB::table('teams')->select('*');

        if ($request->dept_id) {
            $query->where('id', $request->dept_id);
        }

        $data['department'] = $query->get();

        return response()->json($data);
    }

    public function updatePriority(Request $request)
    {
        $validatedData = $request->validate([
            'department_id' => 'required|integer',
            'project_id' => 'required|integer',
            'user_id' => 'required|integer',
            'approval_time' => 'required|numeric',
            'approval_unit' => 'required|string|in:hours,days',
        ]);
        $department_id = $validatedData['department_id'];
        $project_id = $validatedData['project_id'];
        $user_id = $validatedData['user_id'];
        $approval_time = $validatedData['approval_time'];
        $approval_unit = $validatedData['approval_unit'];

        $priorityRole = DB::table('priority_role')
            ->where('user_id', $user_id)
            ->where('department_id', $department_id)
            ->where('project_id', $project_id)
            ->first();

        if ($priorityRole) {
            DB::table('priority_role')
                ->where('user_id', $user_id)
                ->where('department_id', $department_id)
                ->where('project_id', $project_id)
                ->update([
                    'approval_time' => $approval_time,
                    'approval_unit' => $approval_unit,
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true, 'message' => 'Priority updated successfully.']);
        } else {
            DB::table('priority_role')->insert([
                'user_id' => $user_id,
                'department_id' => $department_id,
                'project_id' => $project_id,
                'approval_time' => $approval_time,
                'approval_unit' => $approval_unit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Priority added successfully.']);
        }
    }

    public function getApprovalPriorityTime(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'department_id' => 'required|integer',
            'project_id' => 'required|integer',
        ]);

        $userId = $request->input('user_id');
        $departmentId = $request->input('department_id');
        $projectId = $request->input('project_id');

        $priorityRole = DB::table('priority_role')
            ->where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('project_id', $projectId)
            ->first();

        if ($priorityRole) {
            return response()->json([
                'status' => 'success',
                'data' => $priorityRole,
            ]);
        }
    }

    public function updatePrioritySequency(Request $request)
    {
        $validatedData = $request->validate([
            'department_id' => 'required|integer',
            'project_id' => 'required|integer',
            'user_id' => 'required|integer',
            'priority_select' => 'required|numeric',
        ]);

        $department_id = $validatedData['department_id'];
        $project_id = $validatedData['project_id'];
        $user_id = $validatedData['user_id'];
        $priority_select = $validatedData['priority_select'];

        if($priority_select == 0){
            DB::table('priority_role')
                ->where('department_id', $department_id)
                ->where('project_id', $project_id)
                ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Priority remove successfully.',
            'priority' => '0',
        ]);
        }

        $currentMaxPriority = DB::table('priority_role')
            // ->where('department_id', $department_id)
            ->where('project_id', $project_id)
            ->max('priority_id');

        $expectedPriority = $currentMaxPriority ? $currentMaxPriority + 1 : 1;

        if ($priority_select != $expectedPriority) {
            return response()->json([
                'error' => true,
                'message' => "Invalid priority. The next available priority is {$expectedPriority}.",
            ]);
        }

        $priorityRole = DB::table('priority_role')
            ->where('user_id', $user_id)
            // ->where('department_id', $department_id)
            ->where('project_id', $project_id)
            ->first();

        if ($priorityRole) {
            DB::table('priority_role')
                ->where('user_id', $user_id)
                ->where('department_id', $department_id)
                ->where('project_id', $project_id)
                ->update([
                    'priority_id' => $priority_select,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Priority updated successfully.',
            ]);
        } else {
            DB::table('priority_role')->insert([
                'user_id' => $user_id,
                'department_id' => $department_id,
                'project_id' => $project_id,
                'priority_id' => $priority_select,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Priority added successfully.',
            ]);
        }

    }

    public function getApprovalDeptCount(Request $request)
    {
        $validatedData = $request->validate([
            'department_id' => 'required|integer',
            'project_id' => 'required|integer',
        ]);

        $department_id = $validatedData['department_id'];
        $project_id = $validatedData['project_id'];
        $departmentCount = DB::table('project_members as ppm')
            ->join('employee_details as em', 'em.user_id', '=', 'ppm.user_id')
            ->select(
                DB::raw('(SELECT COUNT(*)
                        FROM project_members sub_ppm
                        INNER JOIN employee_details sub_em
                        ON sub_ppm.user_id = sub_em.user_id
                        WHERE sub_ppm.project_id = ppm.project_id) AS department_count')
            )

            // AND sub_em.department_id = em.department_id
            ->where('ppm.project_id', $project_id)
            ->where('em.department_id', $department_id)
            ->first();
        return response()->json([
            'departmentCount' => $departmentCount->department_count ?? 0,
            'projectId' => $project_id,
            'department_id' => $department_id
        ]);
    }

      public function create_main_project(){
        $this->main_project = DB::table('main_project')->get();
        return view('projects.create_main_project', $this->data);
    }
    public function storeMainProject(Request $request)
    {
        $project_id = DB::table('main_project')->insertGetId([
            'main_project_name' => $request->main_project_name
        ]);

        // Fetch the newly inserted project
        $project = DB::table('main_project')->where('id', $project_id)->first();

        // Fetch all main projects
        $main_projects = DB::table('main_project')->get();

        // Generate options using the correct object
        $options = BaseModel::options($main_projects, $project, 'main_project_name');

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $options]);
    }
    public function updateMainProject(Request $request, $id)
    {
    $existingProject = DB::table('main_project')->where('id', $id)->first();
    if (!$existingProject) {
        return Reply::error(__('messages.recordNotFound'));
    }

    // Update the project
    DB::table('main_project')->where('id', $id)->update([
        'main_project_name' => $request->main_project_name
    ]);

    // Fetch the updated project
    $project = DB::table('main_project')->where('id', $id)->first();

    // Fetch all main projects
    $main_projects = DB::table('main_project')->get();

    // Generate options
    $options = BaseModel::options($main_projects, $project, 'main_project_name');
        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $options]);
    }


    public function getSubprojects(Request $request){
        $projectId = $request->input('project_id');
        $subprojects = Task::where('project_id', $projectId)->get();

        return response()->json(['subprojects' => $subprojects]);
    }

    // public function budgetheads(ImportRequest $request)
    // {
    //     $existingBudgetHead = BudgetHead::where('project_id', $request->project_id)
    //                                  ->where('sub_project_id', $request->subproject_id)
    //                                  ->first();
    //         if ($existingBudgetHead) {
    //             return response()->json([
    //                 'status' => 'confirm',
    //                 'message' => 'Data is already available.',
    //                 'budgetHeadId' => $existingBudgetHead->id
    //             ]);
    //         }

    //     // Save project_id and sub_project_id to budget_heads table
    //     $budgetHead = BudgetHead::create([
    //         'project_id' => $request->project_id,
    //         'sub_project_id' => $request->subproject_id,
    //     ]);
    //  $this->budgetHeadsId = $budgetHead->id;
    //     try {
    //       $this->importFileProcessBudget($request, BudgetHeadImport::class, $budgetHead->id);
    //         $view = view('projects.ajax.budgetHead_import_progress', array_merge($this->data, ['budgetHeadsId' => $budgetHead->id]))->render();

    //         return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    //     } catch (\Exception $e) {
    //         return Reply::error($e->getMessage());
    //     }
    // }

    //  public function budgetheads(ImportRequest $request)
    // {
    //     $existingBudgetHead = BudgetHead::where('project_id', $request->project_id)
    //         ->where('sub_project_id', $request->subproject_id)
    //         ->where('status', 1)
    //         ->first();
    //     if ($existingBudgetHead) {
    //         return response()->json([
    //             'status'       => 'confirm',
    //             'message'      => 'Data is already available.',
    //             'budgetHeadId' => $existingBudgetHead->id,
    //         ]);
    //     }

    //     $existingBudgetHead_id = BudgetHead::where('project_id', $request->project_id)
    //         ->where('sub_project_id', $request->subproject_id)
    //         ->where('status', 2)
    //         ->first()->id;
    //     if ($existingBudgetHead_id) {
    //         // print_r($existingBudgetHead_id);exit;
    //         $budget_HeadsId = $existingBudgetHead_id;
    //     } else {
    //         $budgetHead = BudgetHead::create([
    //             'project_id'     => $request->project_id,
    //             'sub_project_id' => $request->subproject_id,
    //         ]);
    //         $budget_HeadsId = $budgetHead->id;
    //     }
    //     $this->budgetHeadsId = $budget_HeadsId;
    //     try {
    //         $this->importFileProcessBudget($request, BudgetHeadImport::class, $this->budgetHeadsId);
    //         $view = view('projects.ajax.budgetHead_import_progress', array_merge($this->data, ['budgetHeadsId' => $this->budgetHeadsId]))->render();

    //         return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    //     } catch (\Exception $e) {
    //         return Reply::error($e->getMessage());
    //     }
    // }

    public function budgetheads(ImportRequest $request)
{
     $budgetheads = $request->budget_id;

    $this->budgetHeadsId = $budgetheads;
    if(empty($budgetheads)){
    $existingBudgetHead = BudgetHead::where('project_id', $request->project_id)
        ->where('sub_project_id', $request->subproject_id)
        ->where('status', 1)
        ->first();

    if ($existingBudgetHead) {
        return response()->json([
            'status'       => 'confirm',
            'message'      => 'Data is already available.',
            'budgetHeadId' => $existingBudgetHead->id,
        ]);
    }

    // Check if a draft/inactive budget head exists
    $draftBudgetHead = BudgetHead::where('project_id', $request->project_id)
        ->where('sub_project_id', $request->subproject_id)
        ->where('status', 2)
        ->first();

    if ($draftBudgetHead) {
        $budgetHeadsId = $draftBudgetHead->id;
    } else {
        // Create a new budget head if none exists
        $budgetHead = BudgetHead::create([
            'project_id'     => $request->project_id,
            'sub_project_id' => $request->subproject_id,
        ]);
        $budgetHeadsId = $budgetHead->id;
    }

    $this->budgetHeadsId = $budgetHeadsId;
    }else{

    $this->budgetHeadsId = $budgetheads;
    DB::table('budget_head_details')
    ->where('budget_head_id', $budgetheads)
    ->update(['val' => 2]);

    DB::table('budget_head_name')
        ->where('budget_head_id', $budgetheads)
        ->update(['val' => 2]);


    // DB::table('budget_head')
    //     ->where('id', $budgetheads)
    //     ->update(['total_cost' => 0]);
    }

// print_r($this->budgetHeadsId);exit;
    try {
        $this->importFileProcessBudget($request, BudgetHeadImport::class, $this->budgetHeadsId);

        $view = view('projects.ajax.budgetHead_import_progress', array_merge($this->data, [
            'budgetHeadsId' => $this->budgetHeadsId
        ]))->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    } catch (\Exception $e) {
        \Log::error('BudgetHead Import Error: ' . $e->getMessage());
        return Reply::error($e->getMessage());
    }
}



   public function budgetheadsProcess(Request $request,$budgetHeadId)
{
    try {
        $batch = $this->importJobProcessBudget(request(), BudgetHeadImport::class, ImportBudgetHeadJob::class, $budgetHeadId);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    } catch (\Exception $e) {
        return Reply::error($e->getMessage());
    }
}

public function budget_head_details($budgetHeadId, BudgetHeadDetailsDataTable $dataTable)
{
     $viewPermission = user()->permission('view_projects');
        abort_403((!in_array($viewPermission, ['all', 'added', 'owned', 'both'])));

        if (!request()->ajax()) {

            if (in_array('client', user_roles())) {
                $this->clients = User::client();
            }
            else {
                $this->clients = User::allClients();
                $this->allEmployees = User::allEmployees(null, true, ($viewPermission == 'all' ? 'all' : null));
            }

            $this->categories = ProjectCategory::all();
            $this->departments = Team::all();
            $this->projectStatus = ProjectStatusSetting::where('status', 'active')->get();
        }

        return $dataTable->with('budgetHeadId', $budgetHeadId)->render('projects.budget_head_details', $this->data);
}


public function poOrderProcess(Request $request,$orderId)
{


    try {
        $purchaseOrder = PurchaseOrder::find($orderId);

        if (!$purchaseOrder) {
            return Reply::error('Purchase Order not found.');
        }

        $budgetHeadId = $purchaseOrder->budgetHeadId;

        $batch = $this->importJobProcessPO(request(), POOrderImport::class, ImportPOOrderJob::class, $orderId,$budgetHeadId);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    } catch (\Exception $e) {
        return Reply::error($e->getMessage());
    }
}

    public function importStagesPo(Request $request, $orderId)
    {
        $this->pageTitle = __('app.importExcel');
        $this->orderId = $orderId;
        $this->view = 'projects.ajax.importStages';

        if ($request->ajax()) {
            return $this->returnAjax($this->view, ['orderId' => $orderId]);
        }

        return view('purchase::purchase-order.create', $this->data);
    }


    public function stagesPOImport(Request $request)
     {
            //  print_r($request->input('purchase_order_id'));exit;
        try {
            $this->importFileProcessPoStages($request, StagesImport::class, $request->purchase_order_id, $request->stages);


            $view = view('projects.ajax.stages_po_process', array_merge(
                $this->data,
                [
                    'purchase_order_id' => $request->purchase_order_id,
                    'stages' => $request->stages,
                ]
            ))->render();

            return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
        } catch (\Exception $e) {
            return Reply::error($e->getMessage());
        }
    }

    public function stagesProcess(Request $request, $orderId, $stages)
    {
        try {
            $batch = $this->importJobProcessPoStages(request(), StagesImport::class, ImportStagesJob::class, $orderId,$stages);

            return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
        } catch (\Exception $e) {
            return Reply::error($e->getMessage());
        }
    }


  public function budgetHeadList($id)
    {
        $projectId = $id;
        $dataTable = new ProjectwiseBudgetHeadDataTable($projectId);
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'projects.ajax.budgetHeadList';
        return $dataTable->render('projects.show', $this->data);
    }
    public function purchaseOrder($id)
    {
        $projectId = $id;
        $dataTable = new ProjectwisePODataTable($projectId);
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'projects.ajax.purchaseOrderList';
        return $dataTable->render('projects.show', $this->data);
    }
    public function purchaseBillList($id)
    {
        $projectId = $id;
        $dataTable = new ProjectwisePOBillDataTable($projectId);
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'projects.ajax.purchaseBillList';
        return $dataTable->render('projects.show', $this->data);
    }
  public function checkBHforproject(Request $request)
    {
        $projectId    = $request->input('project_id');
        $subprojectId = $request->input('subproject_id');

        if ($projectId) {
            $priority_role = DB::table('priority_role')->where('project_id', $projectId)->first();
            if (empty($priority_role)) {
                return response()->json(['status' => 'error', 'message' => 'Priority role not found.']);
            }
        }

        if ($projectId && $subprojectId) {
            $budgetHeadList = DB::table('budget_head')
                ->where('project_id', $projectId)
                ->where('sub_project_id', $subprojectId)
                ->where('action', 1)
                ->first();

            if ($budgetHeadList) {
                return response()->json(['status' => 'success', 'message' => 'Budget head already exists.']);
            } else {
                return response()->json(['status' => 'error1', 'message' => 'Budget head does not exist.']);
            }
        }
    }

public function check_po_number(Request $request)
    {
        $bill_type = $request->bill_type;
        $project_id = $request->project_id;
        $subproject_id = $request->subproject_id;
        $category = $request->category;
        $prefix = $request->prefix;
        $categorytype = $request->categorytype;
        $order_type = $request->order_type;


        $lastNumber = DB::table('purchase_orders')
                    ->selectRaw('CAST(SUBSTRING_INDEX(po_number, "/", -1) AS UNSIGNED) AS last_number')
                    ->where('project_id', $project_id)
                    // ->where('subproject_id', $subproject_id)
                    ->where('order_type', $order_type)
                    ->orderByDesc('last_number')
                    ->limit(1)
                    ->value('last_number');

                $lastNumber = $lastNumber ?: 0;



        $purchase_order_number = ($lastNumber == 0) ? 1 : $lastNumber + 1;

        $project = DB::table('projects')
            ->where('id', $project_id)
            ->select('project_short_code', 'client_id')
            ->first();

       $heading = DB::table('tasks')
            ->where('id', $subproject_id)
            ->value('heading');

        $projectShortCode = $project->project_short_code ?? null;

        $now = now();
        $year = $now->year;
        $month = $now->month;

        if ($month >= 4) {
            $startYear = $year % 100;
            $endYear = ($year + 1) % 100;
        } else {
            $startYear = ($year - 1) % 100;
            $endYear = $year % 100;
        }

       if ($categorytype == 'material') {
            $ct = 'M';
        } elseif ($categorytype == 'labour') {
            $ct = 'L';
        } else {
            return '';
        }

        $financialYear = sprintf('%02d-%02d', $startYear, $endYear);

        // Determine PO number format
        if ($order_type == 'purchase_order') {
            $ot = "PO";
        } elseif ($order_type == 'work_order') {
            $ot = "WO";
        } else {
            return response()->json(['error' => 'Invalid order type'], 400);
        }

        if ($category == 'contractor') {
            $ca = "WRKC";
        } elseif ($category == 'consultant') {
            $ca = "CONS";
        } elseif ($category == 'supplier') {
            $ca = "SUPP";
        } else {
            return response()->json(['error' => 'Invalid category'], 400);
        }

        $convert_purchase_order_number = str_pad($purchase_order_number, 7, '0', STR_PAD_LEFT);


        if ($bill_type == "project") {
            $po_number = $prefix . $projectShortCode . "/" . $ot . "/" . $ca . "/" . $financialYear . "/" .$heading. "/" . $ct . "/" . $purchase_order_number;
        } elseif ($bill_type == "thanish__wo") {
            $po_number = '912'  . $convert_purchase_order_number;
        } elseif ($bill_type == "planet_wo") {
            $po_number = '212' . $convert_purchase_order_number;
        } elseif ($bill_type == "thanish__po") {
            $po_number = '911' . $convert_purchase_order_number;
        } elseif ($bill_type == "planet_po") {
            $po_number = '211' . $convert_purchase_order_number;
        } else {
            return response()->json(['error' => 'Invalid bill type'], 400);
        }
 
        $exists = DB::table('purchase_orders')->where('po_number', $po_number)->exists();

        return response()->json([
            'exists' => $exists,
            'po_number' => $po_number,
            'purchase_order_number'=>$purchase_order_number,
        ]);
    }



    public function companyGroup(CompanyGroupDataTable $dataTable)
    {
        $this->pageTitle = __('app.companyGroup');
        return $dataTable->render('company-groups.index', $this->data);
    }

    public function submitcompanyGroup(Request $request)
    {
        DB::table('company_group')->insert([
            'company_name' => $request->company_group,
        ]);

        return redirect()->route('projects.companyGroup')->with('success', 'Company Group created successfully.');
    }

    public function updatecompanyGroup(Request $request)
    {
        DB::table('company_group')->where('id', $request->id)->update([
            'company_name' => $request->company_group,
        ]);

        return redirect()->route('projects.companyGroup')->with('success', 'Company Group created successfully.');
    }

    public function checkDuplicateCompanyGroup(Request $request)
    {
        $exists = DB::table('company_group')
            ->where('company_name', $request->company_name)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function billingcompanyGroup(BillingCompanyDataTable $dataTable)
    {
        $this->pageTitle = __('app.billingcompany');
        return $dataTable->render('billing-company.index', $this->data);
    }
    public function getCompanyGroup()
    {
        $this->companies = DB::table('client_details')->get();

        return response()->json($this->companies);
    }

    public function checkDuplicatebillingCompanygroup(Request $request)
    {
        $exists = DB::table('billing_company')
            ->where('billing_company_name', $request->billing_company)
            ->exists();

        return response()->json(['exists' => $exists]);
    }
    public function submitbillingcompanyGroup(Request $request)
    {
        DB::table('billing_company')->insert([
            'company_group_id'     => $request->companyName,
            'billing_company_name' => $request->billing_company,

        ]);

        return redirect()->route('projects.billingcompanyGroup')->with('success', 'Billing Company created successfully.');
    }

    public function updatebillingcompanyGroup(Request $request)
    {
        DB::table('billing_company')->where('id', $request->id)->update([
            'company_group_id'     => $request->companyName,
            'billing_company_name' => $request->billing_company,
        ]);

        return redirect()->route('projects.billingcompanyGroup')->with('success', 'Company Group created successfully.');
    }

public function getBillingCompanies(Request $request)
    {
        $companyGroupId = $request->company_group_id;
        // print_r($companyGroupId);
    // exit;
        $billingCompanies =  DB::table('billing_company')
                            ->where('status', 1)
                            ->where('company_group_id',$companyGroupId)
                            ->select('id', 'billing_company_name')
                            ->get();

        return response()->json($billingCompanies);
    }

  public function getBreakup(Request $request)
{
    $productId = $request->input('productId');
    $poId = $request->input('poId');

    // Get the first purchase_settings record (assuming company_id is a valid key for filtering)
    $purchaseSetting = DB::table('purchase_settings')
        ->where('company_id', auth()->user()->company_id) // Assuming you need to filter by company_id
        ->first();

    if (!$purchaseSetting) {
        return response()->json(['error' => 'Purchase settings not found'], 404);
    }

    // Fetch the relevant purchase_bill_items
    $items = DB::table('purchase_bill_items')
        ->join('purchase_bills', 'purchase_bill_items.purchase_bills_id', '=', 'purchase_bills.id')
        ->select(
            'purchase_bill_items.quantity',
            'purchase_bill_items.purchase_bills_id',
            'purchase_bills.reference_bill_no',
             'purchase_bills.bill_date'
        )
        ->where('purchase_bill_items.product_id', $productId) // Use dynamic productId
        ->where('purchase_bills.purchase_order_id', $poId) // Use dynamic poId
        ->where('purchase_bill_items.quantity', '>', 0)
        ->get()
        ->map(function ($item) use ($purchaseSetting) {
            // Extract numeric part from purchase_bills_id for padding
            $numberOnly = preg_replace('/\D/', '', $item->purchase_bills_id);

            // Pad the number based on bill_number_digit
            $paddedNumber = str_pad($numberOnly, $purchaseSetting->bill_number_digit, '0', STR_PAD_LEFT);

            // Construct final bill number
            $item->bill_number = $purchaseSetting->bill_prefix . $purchaseSetting->bill_number_separator . $paddedNumber;

            return $item;
        });

    // Return the formatted data as JSON
    return response()->json(['data' => $items]);
}

}
