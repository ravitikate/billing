<?php
namespace App\DataTables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Helper\NumberFormat;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Html\Button;

NumberFormat::formatIndianCurrency(1234567);

class BudgetVsActualDataTable extends BaseDataTable
{
    private $addBudgetPermission;
    private $editBudgetPermission;

    public function __construct()
    {
        parent::__construct();
        $this->addBudgetPermission = user()->permission('add_budget');
        $this->editBudgetPermission = user()->permission('edit_budget');

    }

    /**
     * Process the data and add action columns.
     *
     * @param  mixed  $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()->of($query)
            ->addColumn('index', function ($row) {
                static $index = 1;
                return $index++;
            })

    ->addColumn('action', function ($row) {
    $action = '';

    // Status badges
    if ($row->validate == 1 && $row->status == 1) {
        $action .= '
            <span class="btn btn-xs btn-success me-1" title="Validate">
                <i class="fas fa-check-circle"></i>
            </span>';
    } elseif ($row->validate == 2 && $row->status == 1) {
        $action .= '
            <a href="' . route('budget.budgetHeaddetails', [$row->id]) . '" 
               class="btn btn-xs btn-primary me-1" 
               title="Non-Validated">
                <i class="fas fa-exclamation-circle"></i>
            </a>';
    } elseif ($row->validate == 2 && $row->status == 2) {
        $action .= '
            <button type="button" 
                    class="btn btn-xs btn-danger me-1" 
                    data-id="' . $row->id . '" 
                    data-bs-toggle="modal" 
                    data-bs-target="#rejectReasonModal"
                    title="Rejected">
                <i class="fas fa-ban"></i>
            </button>';
    }

    // Edit Budget button
    if ($this->editBudgetPermission === 'all' 
        || $this->editBudgetPermission === 'added' 
        || $this->editBudgetPermission === 'both') {
        $action .= '
            <a href="' . route('budget.edit_budget', [$row->id]) . '" 
               class="btn btn-xs btn-warning me-1" 
               title="Edit Budget">
                <i class="fas fa-pencil-alt"></i>
            </a>';
    }

    // Budget Details button
    $action .= '
        <a href="' . route('budget.budgetHeaddetails', [$row->id]) . '" 
           class="btn btn-xs btn-info me-1" 
           title="Budget Details">
            <i class="fas fa-info-circle"></i> 
        </a>';

    // View Actual button
    $action .= '
        <a href="' . route('budget.actualvereal', [$row->id]) . '" 
           class="btn btn-xs btn-success" 
           title="View Actual">
            <i class="fas fa-chart-line"></i>
        </a>';

    return $action;
})

->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '"
                           class="text-decoration-none">
                            ' . ucfirst($row->project_name) . '
                        </a>';
            })
            ->addColumn('task_info', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '
                           class="text-decoration-none">
                            ' . ucfirst($row->task_info) . '
                        </a>';
            })

            ->editColumn('total_budget_cost', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->total_budget_cost));
            })
            ->editColumn('budget_PO', function ($row) {
               return '₹ ' . NumberFormat::formatIndianCurrency(round($row->budget_PO + $row->budget_PO_tax));
            })
            ->editColumn('actual_cost', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->netAmount));
            })
            ->editColumn('debit_cost', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->totalAmount) - round($row->netAmount));
            })
            ->editColumn('tcost', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->totalAmount) );
            })
            ->editColumn('acost', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->netAmount));
            })
            ->editColumn('partPayment', function ($row) {
                return '₹ ' . NumberFormat::formatIndianCurrency(round($row->partPayment)-round($row->netAmount));
            })
            ->rawColumns(['index', 'project_name','task_info','action','total_budget_cost','budget_PO','actual_cost','partPayment','debit_cost','tcost','acost']);
    }




    /**
     * Build your query for the data.
     *
     * @param  Request  $request
     * @return \Illuminate\Database\Query\Builder
     */
public function query(Request $request)
{
    $user = Auth::user();

    $query = DB::table('budget_head as bh')
        ->join('projects as p', 'p.id', '=', 'bh.project_id')
        ->join('tasks as t', 't.id', '=', 'bh.sub_project_id');

    // Apply user restriction if not admin
    if (!in_array('admin', user_roles())) {
        $userId = $user->id;
        $query->whereExists(function ($q) use ($userId) {
            $q->select(DB::raw(1))
              ->from('project_members as pm')
              ->whereRaw('pm.project_id = p.id')
              ->where('pm.user_id', $userId);
        });
    }

    $query->select(
        'bh.id',
        'bh.validate',
        'bh.status',
        'p.project_name',
        'p.id as project_id',
        't.id as task_id',
        DB::raw("CONCAT(t.heading) AS task_info"),

        DB::raw("(
            CASE
                WHEN bh.status = 2 AND bh.validate = 2 THEN (
                    SELECT SUM(bh_details.budget_cost)
                    FROM budget_head_details bh_details
                    WHERE bh_details.budget_head_id = bh.id
                      AND bh_details.val = 2 AND bh_details.status = 2
                )
                WHEN bh.status = 1 AND bh.validate = 2 THEN (
                    SELECT SUM(bh_details.budget_cost)
                    FROM budget_head_details bh_details
                    WHERE bh_details.budget_head_id = bh.id
                      AND bh_details.val = 2 AND bh_details.status = 2
                )
                WHEN bh.status = 1 AND bh.validate = 1 THEN (
                    SELECT SUM(bh_details.budget_cost)
                    FROM budget_head_details bh_details
                    WHERE bh_details.budget_head_id = bh.id
                      AND bh_details.val = 1
                )
                ELSE NULL
            END
        ) AS total_budget_cost"),

        DB::raw("(
            SELECT SUM(pi.amount)
            FROM purchase_items pi
            JOIN purchase_orders po ON po.id = pi.purchase_order_id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND pi.po_type = 'boq'
              AND po.type = 'boq'
              AND po.validate = 'true'
              AND po.status = 'true'
        ) AS budget_PO"),

        DB::raw("(
            SELECT SUM(pi.gst_amount)
            FROM purchase_items pi
            JOIN purchase_orders po ON po.id = pi.purchase_order_id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND po.validate = 'true'
              AND po.status = 'true'
              AND pi.po_type = 'boq'
        ) AS budget_PO_tax"),

        DB::raw("(
            SELECT SUM(ppb.total_paid)
            FROM purchase_payment_bills ppb
            JOIN purchase_bills pb ON pb.id = ppb.purchase_bill_id
            JOIN purchase_orders po ON po.id = pb.purchase_order_id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
        ) AS partPayment"),

        DB::raw("(
            SELECT SUM(pb.total)
            FROM purchase_bills pb
            JOIN purchase_orders po ON po.id = pb.purchase_order_id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND pb.status = 'paid'
        ) AS totalAmount"),

        DB::raw("(
            SELECT SUM(pb.netpayableamount)
            FROM purchase_bills pb
            JOIN purchase_orders po ON po.id = pb.purchase_order_id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND pb.status = 'paid'
        ) AS netAmount"),

         DB::raw("(
            SELECT SUM(pbi.amount)
            FROM purchase_bills pb
            JOIN purchase_orders po ON po.id = pb.purchase_order_id
            JOIN purchase_bill_items pbi ON pbi.purchase_bills_id = pb.id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND pb.status = 'open'
        ) AS actual_cost"),

        DB::raw("(
            SELECT SUM(pbi.tax_amount)
            FROM purchase_bills pb
            JOIN purchase_orders po ON po.id = pb.purchase_order_id
            JOIN purchase_bill_items pbi ON pbi.purchase_bills_id = pb.id
            WHERE po.project_id = bh.project_id
              AND po.subproject_id = bh.sub_project_id
              AND pb.status = 'open'
        ) AS tax_actual_amount")
    );

    // Filters
    if ($request->project_id) {
        $query->where('bh.project_id', $request->project_id);
    }

    if ($request->subproject_id) {
        $query->where('bh.sub_project_id', $request->subproject_id);
    }

    if (!empty($request->searchText)) {
        $search = $request->searchText;
        $query->where(function ($q) use ($search) {
            $q->where('p.project_name', 'like', '%' . $search . '%')
              ->orWhere('t.task_short_code', 'like', '%' . $search . '%')
              ->orWhere('t.heading', 'like', '%' . $search . '%');
        });
    }

    return $query->orderBy('bh.id');
}






    /**
     * Configure the DataTable's HTML structure.
     *
     * @return \Yajra\DataTables\Html\Builder
     */


 public function html()
    {
        $dataTable = $this->setBuilder('budgetHeadsTable')
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["budgetHeadsTable"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                  //
                }',
                /* 'buttons'      => ['excel'] */
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }


    protected function getColumns()
    {
        return [
              Column::make('index')->title('#'), // This will now show the row index number
                Column::make('project_name')->title(__('app.project')),
                Column::make('task_info')->title(__('app.subproject')),
                column::make('total_budget_cost')->title(__('app.budget_data')),
                column::make('budget_PO')->title(__('app.committedAmt')),
                column::make('partPayment')->title(__('app.partPayment')),
                column::make('debit_cost')->title(__('app.debit_cost')),
                column::make('actual_cost')->title(__('app.actualAmt')),
                Column::make('action')->title('Actions'),
        ];
    }

}
