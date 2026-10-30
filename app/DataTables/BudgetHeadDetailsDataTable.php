<?php
 
namespace App\DataTables;

use App\Models\BudgetHeadDetail;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class BudgetHeadDetailsDataTable extends BaseDataTable
{
    protected $budgetHeadId;

    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if ($k === 'budgetHeadId') {
                    $this->budgetHeadId = $v;
                }
            }
        } elseif ($key === 'budgetHeadId') {
            $this->budgetHeadId = $value;
        }

        return $this;
    }

   public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn(); // This will add the DT_RowIndex column
    }

   public function query(BudgetHeadDetail $model)
    {
        return $model->select('*', DB::raw('CAST(wbs AS UNSIGNED) as wbs_numeric'))
                     ->where('budget_head_id', $this->budgetHeadId)
                     ->orderBy('wbs_numeric', 'asc');
    }


    public function html()
{
    $dataTable = $this->setBuilder('budget_head_details-table')
        ->parameters([
            'pageLength' => 50, // Set default rows per page to 50
            'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]], // Customize dropdown options
            'initComplete' => 'function () {
                window.LaravelDataTables["budget_head_details-table"].buttons().container()
                    .appendTo("#table-actions")
            }',
            'fnDrawCallback' => 'function() {
                $("#budget_head_details-table .select-picker").selectpicker();
                $(".bs-tooltip-top").removeClass("show");
            }',
        ]);

    if (canDataTableExport()) {
        $dataTable->buttons(Button::make([
            'extend' => 'excel',
            'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')
        ]));
    }

    return $dataTable;
}


    protected function getColumns()
    {
        return [
        //     '#' => [
        //     'data' => 'DT_RowIndex',
        //     'name' => 'DT_RowIndex',
        //     'orderable' => false, // Enable sorting
        //     'searchable' => false,
        //     'title' => '#'
        // ],
            __('app.wbs') => ['data' => 'wbs', 'name' => 'wbs', 'title' => __('app.wbs')],
            __('app.budget_head') => ['data' => 'budget_head', 'name' => 'budget_head', 'title' => __('app.budget_head')],
            __('app.budget_cost') => ['data' => 'budget_cost', 'name' => 'budget_cost', 'title' => __('app.budget_cost')],
        ];
    }
}
