<?php
namespace App\DataTables;

use App\Models\Budget;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BudgetDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()->eloquent($query)
            ->addColumn('budget_number', function ($row) {
                $url = route('budget.edit', [$row->id]); // Ensure this route exists
                return '<a href="' . $url . '">' . e($row->budget_number) . '</a>';
            })
            ->addColumn('project_name', function ($row) {
                $url = route('projects.show', [$row->project_id]); // Ensure this route exists
                return '<a href="' . $url . '">' . e($row->project_name) . '</a>';
            })
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">
                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                $action .= '<a href="' . route('budget.edit', [$row->id]) . '" class="dropdown-item"><i class="fa fa-edit mr-2"></i>' . __('app.edit') . '</a>';

                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })
            ->rawColumns(['budget_number', 'project_name', 'action']); // Added budget_number to prevent escaping
    }

    public function query(Budget $model)
    {
        return $model->newQuery()
            ->select('budgets.*', 'projects.project_name', 'projects.id as project_id') // Include project_id
            ->join('projects', 'projects.id', '=', 'budgets.project_id'); // Join projects table
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('budget-table')
            ->columns([
                Column::make('budget_number'),
                Column::make('project_name'),
                Column::make('valid_till'),
                Column::make('sub_total'),
                Column::make('total'),
                Column::computed('action'),
            ])
            ->minifiedAjax()
            ->orderBy(1);
    }
}
