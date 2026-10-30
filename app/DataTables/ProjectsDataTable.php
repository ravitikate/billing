<?php
namespace App\DataTables;

use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\Models\Project;
use App\Models\ProjectStatusSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ProjectsDataTable extends BaseDataTable
{

    private $addProjectPermission;
    private $editProjectsPermission;
    private $deleteProjectPermission;
    private $viewProjectPermission;
    private $viewGanttPermission;
    private $addProjectMemberPermission;

    public function __construct()
    {
        parent::__construct();

        $this->addProjectPermission       = user()->permission('add_projects');
        $this->editProjectsPermission     = user()->permission('edit_projects');
        $this->deleteProjectPermission    = user()->permission('delete_projects');
        $this->viewProjectPermission      = user()->permission('view_projects');
        $this->viewGanttPermission        = user()->permission('view_project_gantt_chart');
        $this->addProjectMemberPermission = user()->permission('add_project_members');

        Log::debug('Project permissions initialized', [
            'add'       => $this->addProjectPermission,
            'edit'      => $this->editProjectsPermission,
            'delete'    => $this->deleteProjectPermission,
            'view'      => $this->viewProjectPermission,
            'gantt'     => $this->viewGanttPermission,
            'addMember' => $this->addProjectMemberPermission,
        ]);
    }

    public function dataTable($query)
    {
        $projectStatus = ProjectStatusSetting::get();
        $datatables    = datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $memberIds = $row->members->pluck('user_id')->toArray();

                $action = '<div class="task_view">
                <div class="dropdown">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                $action .= '<a href="' . route('projects.show', [$row->id]) . '" class="dropdown-item">
                            <i class="fa fa-eye mr-2"></i>' . __('app.view') . '
                        </a>';

                if (
                    $this->editProjectsPermission == 'all'
                    || ($this->editProjectsPermission == 'added' && user()->id == $row->added_by)
                    || ($this->editProjectsPermission == 'owned' && user()->id == $row->client_id && in_array('client', user_roles()))
                    || ($this->editProjectsPermission == 'owned' && in_array(user()->id, $memberIds) && in_array('employee', user_roles()))
                    || ($this->editProjectsPermission == 'both' && (user()->id == $row->client_id || user()->id == $row->added_by))
                    || ($this->editProjectsPermission == 'both' && in_array(user()->id, $memberIds) && in_array('employee', user_roles()))
                ) {
                    $action .= '<a class="dropdown-item openRightModal" href="' . route('projects.edit', [$row->id]) . '">
                            <i class="fa fa-edit mr-2"></i>
                            ' . trans('app.edit') . '
                        </a>';
                }

                $action .= '</div>
                </div>
            </div>';

                return $action;
            })
            ->addColumn('project', function ($row) {
                return '<div class="media align-items-center">
                        <div class="media-body">
                    <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('projects.show', [$row->id]) . '">' . $row->project_name . '</a></h5>
                    </div>
                </div>';
            })
            ->addColumn('project_short_code', function ($row) {

                $projectPrefixSetting = DB::table('project_prefix_settings')->where('id', 1)->first();
                $paddedNumber         = str_pad($row->project_short_code, $projectPrefixSetting->project_number_digit, '0', STR_PAD_LEFT);
                $fullCode             = $projectPrefixSetting->project_prefix . $projectPrefixSetting->project_number_separator . $paddedNumber;
                return '<div class="media align-items-center">
                        <div class="media-body">
                    <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('projects.show', [$row->id]) . '">' . $fullCode . '</a></h5>
                    </div>
                </div>';
            })
            ->addColumn('status', function ($row) use ($projectStatus) {
                $projectUsers = $row->members->pluck('user_id')->toArray();

                if ($row->completion_percent < 50) {
                    $statusColor = 'danger';
                } elseif ($row->completion_percent < 75) {
                    $statusColor = 'warning';
                } else {
                    $statusColor = 'success';
                }

                $status = '<p><div class="progress" style="height: 15px;">
                <div class="progress-bar f-12 bg-' . $statusColor . '" role="progressbar" style="width: ' . $row->completion_percent . '%;" aria-valuenow="' . $row->completion_percent . '" aria-valuemin="0" aria-valuemax="100">' . $row->completion_percent . '%</div>
              </div></p>';

                if (
                    $this->editProjectsPermission == 'all'
                    || ($this->editProjectsPermission == 'added' && user()->id == $row->added_by)
                    || ($this->editProjectsPermission == 'owned' && user()->id == $row->client_id && in_array('client', user_roles()))
                    || ($this->editProjectsPermission == 'owned' && in_array(user()->id, $projectUsers) && in_array('employee', user_roles()))
                    || ($this->editProjectsPermission == 'both' && (user()->id == $row->client_id || user()->id == $row->added_by))
                    || ($this->editProjectsPermission == 'both' && in_array(user()->id, $projectUsers) && in_array('employee', user_roles()))
                ) {
                    $status .= '<select class="form-control select-picker change-status" data-project-id="' . $row->id . '">';

                    foreach ($projectStatus as $item) {
                        $selected = $item->status_name == $row->status ? 'selected' : '';
                        $status .= '<option ' . $selected . ' data-content="<i class=\'fa fa-circle mr-2\' style=\'color: ' . $item->color . '\'></i> ' . $item->status_name . '" value="' . $item->status_name . '">' . $item->status_name . '</option>';
                    }

                    $status .= '</select>';

                    return $status;
                } else {
                    foreach ($projectStatus as $item) {
                        if ($row->status == $item->status_name) {
                            return '<i class="fa fa-circle mr-1 text-yellow" style="color: ' . $item->color . '"></i>' . $item->status_name;
                        }
                    }
                }
            });
        $customFieldColumns = CustomField::customFieldData($datatables, Project::CUSTOM_FIELD_MODEL);
        $datatables->rawColumns(array_merge(['action', 'status', 'project', 'project_short_code'], $customFieldColumns));

        return $datatables;
    }

    
 public function query(Project $model)
{
    $request = request(); // Get the request instance

    // Start building the query
    $query = $model->newQuery()
        ->leftJoin('project_members', 'project_members.project_id', 'projects.id')
        ->leftJoin('users', 'project_members.user_id', 'users.id')
        ->leftJoin('users as client', 'projects.client_id', 'users.id')
        ->leftJoin('mention_users', 'mention_users.project_id', 'projects.id')
        ->leftJoin('client_details', 'client_details.user_id', 'projects.client_id')
        ->groupBy('projects.id')
        ->select([
            'projects.id',
            'projects.project_short_code',
            'projects.hash',
            'projects.added_by',
            'projects.project_name',
            'projects.start_date',
            'projects.deadline',
            'projects.client_id',
            'projects.completion_percent',
            'projects.project_budget',
            'projects.currency_id',
            'projects.status',
            'client_details.company_name as client_name'
        ]);

    // Apply search filter
    if ($request->searchText != '') {
        $query->where(function ($q) use ($request) {
            $q->where('projects.project_name', 'like', '%' . $request->searchText . '%')
              ->orWhere('users.name', 'like', '%' . $request->searchText . '%')
              ->orWhere('projects.project_short_code', 'like', '%' . $request->searchText . '%');
        });
    }

    // Apply status filters
    if ($request->status != 'overdue' && !is_null($request->status) && $request->status != 'all') {
        // Apply date filters for non-overdue statuses
        if ($request->startFilterDate !== null && $request->startFilterDate != 'null' && $request->startFilterDate != '') {
            $startFilterDate = companyToDateString($request->startFilterDate);
            $query->where(DB::raw('DATE(projects.`start_date`)'), '>=', $startFilterDate);
        }

        if ($request->endFilterDate !== null && $request->endFilterDate != 'null' && $request->endFilterDate != '') {
            $endFilterDate = companyToDateString($request->endFilterDate);
            $query->where(DB::raw('DATE(projects.`start_date`)'), '<=', $endFilterDate);
        }

        $query->where('projects.status', $request->status);
    }

    // Apply overdue or other status filters
    if (!is_null($request->status) && $request->status != 'all') {
        if ($request->status == 'overdue') {
            $query->where('projects.completion_percent', '!=', 100);
            $todayDate = now(company()->timezone)->toDateString();

            if ($request->deadLineStartDate == '' && $request->deadLineEndDate == '') {
                if ($request->startFilterDate == '' && $request->endFilterDate == '') {
                    $query->whereDate('projects.deadline', '<', $todayDate);
                } else {
                    $startDate = companyToDateString($request->startFilterDate);
                    $endDate = companyToDateString($request->endFilterDate);

                    // Check if today's date is between start date and end date
                    if ($todayDate >= $startDate && $todayDate <= $endDate) {
                        $query->whereRaw('Date(projects.deadline) >= ?', [$startDate])
                            ->whereRaw('Date(projects.deadline) < ?', [$todayDate]);
                    } else {
                        $query->whereRaw('Date(projects.deadline) >= ?', [$startDate])
                            ->whereRaw('Date(projects.deadline) <= ?', [$endDate]);
                    }
                }
            } else {
                if ($request->startFilterDate == '' && $request->endFilterDate == '') {
                    $startDate = companyToDateString($request->deadLineStartDate);
                    $endDate = companyToDateString($request->deadLineEndDate);
                } else {
                    $startDate = companyToDateString($request->startFilterDate);
                    $endDate = companyToDateString($request->endFilterDate);
                }

                // Check if today's date is between start date and end date
                if ($todayDate >= $startDate && $todayDate <= $endDate) {
                    $query->whereRaw('Date(projects.deadline) >= ?', [$startDate])
                        ->whereRaw('Date(projects.deadline) < ?', [$todayDate]);
                } else {
                    $query->whereRaw('Date(projects.deadline) >= ?', [$startDate])
                        ->whereRaw('Date(projects.deadline) <= ?', [$endDate]);
                }
            }
        } else {
            $query->where('projects.status', $request->status);
        }
    } else {
        // Apply date filters when status is 'all' or null
        if ($request->startFilterDate !== null && $request->startFilterDate != 'null' && $request->startFilterDate != '') {
            $startFilterDate = companyToDateString($request->startFilterDate);
            $query->where(DB::raw('DATE(projects.`start_date`)'), '>=', $startFilterDate);
        }

        if ($request->endFilterDate !== null && $request->endFilterDate != 'null' && $request->endFilterDate != '') {
            $endFilterDate = companyToDateString($request->endFilterDate);
            $query->where(DB::raw('DATE(projects.`start_date`)'), '<=', $endFilterDate);
        }
    }

    return $query;
}

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('projects-table', 2)
            ->parameters(
                [
                    'initComplete'   => 'function () {
                    window.LaravelDataTables["projects-table"].buttons().container()
                     .appendTo( "#table-actions")
                 }',
                    'fnDrawCallback' => 'function( oSettings ) {
                    $("#projects-table .select-picker").selectpicker();

                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
                ]
            );

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $data = [
            'Sr. No'                           => [ // Label for the serial number column
                'data'       => 'DT_RowIndex',          // This provides row index automatically
                'orderable'  => false,
                'searchable' => false,
                'visible'    => true,     // Make it visible
                'title'      => 'Sr. No', // Column header text
            ],
            __('modules.taskCode')             => [
                'data'  => 'project_short_code',
                'name'  => 'project_short_code',
                'title' => __('modules.taskCode'),
            ],
            __('modules.projects.projectName') => [
                'data'       => 'project',
                'name'       => 'project',
                'exportable' => false,
                'width'      => '20%',
                'title'      => __('modules.projects.projectName'),
            ],
            __('modules.projects.client')      => [
                'data'       => 'client_name',
                'name'       => 'client_name',
                'exportable' => false,
                'width'      => '20%',
                'title'      => __('modules.projects.client'),
            ],
            __('app.status')                   => [
                'data'       => 'status',
                'name'       => 'status',
                'width'      => '16%',
                'exportable' => false,
                'visible'    => true,
                'title'      => __('app.status'),
            ],
        ];

        $action = [
            'action' => [
                'title'      => __('app.action'),
                'orderable'  => false,
                'searchable' => false,
                'exportable' => false,
                'printable'  => false,
                'width'      => '15%',
                'class'      => 'text-center',
            ],
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Project()), $action);
    }

}
