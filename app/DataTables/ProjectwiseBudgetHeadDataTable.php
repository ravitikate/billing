<?php
namespace App\DataTables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\Project;

class ProjectwiseBudgetHeadDataTable extends DataTable
{
    protected $projectId;

    public function __construct($projectId = null)
    {
        $this->projectId = $projectId;
    }
    public function dataTable($query)
    {
        return datatables()->of($query)
            ->addColumn('index', function ($row) {
                static $index = 1;
                return $index++;
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('budget.budgetHeaddetails', [$row->id]) . '" class="btn btn-sm">
                            <i class="fa fa-eye"></i> View Details
                        </a>';
            })
            ->rawColumns(['index', 'action']);
    }

    public function query(Project $model)
    {
    //    dd($this->projectId);
       return DB::table('budget_head as bh')
            ->join('projects as p', 'p.id', '=', 'bh.project_id')
            ->join('tasks as t', 't.id', '=', 'bh.sub_project_id')
            ->select(
                'bh.id',
                'p.project_name',
                DB::raw("CONCAT(t.task_short_code, ' - ', t.heading) AS task_info"),
                DB::raw("(SELECT SUM(bh_details.budget_cost) FROM budget_head_details bh_details WHERE bh_details.budget_head_id = bh.id) AS total_budget_cost")
            )->where('bh.project_id', $this->projectId);

    }


    public function html()
    {
        return $this->builder()
            ->setTableId('budgetHeadsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0);
            // ->parameters([
            //     'dom' => 'Bfrtip',
            //     'buttons' => ['excel', 'csv', 'print'],
            // ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('index')->title('#'), // This will now show the row index number
            Column::make('project_name')->title('Project Name'),
            Column::make('task_info')->title('Budget Head'),
            column::make('total_budget_cost')->title('Total Budget Cost'),
            Column::make('action')->title('Actions'), // Add the Action column to display the Edit button
        ];
    }

}
