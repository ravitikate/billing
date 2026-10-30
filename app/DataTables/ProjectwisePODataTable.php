<?php
namespace App\DataTables;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\Project;

class ProjectwisePODataTable extends DataTable
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
            ->addColumn('po_number', function ($row) {
              $po_no = sprintf('PO#%03d', $row->purchase_order_number);
                return $po_no;
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('purchase-order.show', [$row->id]) . '" class="btn btn-sm ">
                            <i class="fa fa-eye"></i> View
                        </a>';
            })
            ->editColumn('total', function ($row) {
                return '₹' . number_format($row->total, 2);
            })
            ->rawColumns(['index','po_number', 'action']);
    }

    public function query(Project $model)
    {
    //    dd($this->projectId);
       return DB::table('purchase_orders as po')
            ->join('purchase_vendors as pv', 'pv.id', '=', 'po.vendor_id')
            ->select(
                'po.*',
                'pv.primary_name'
            )->where('po.project_id', $this->projectId);

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
            Column::make('index')->title('#'),
            Column::make('po_number')->title(__('app.po_number')),
            Column::make('primary_name')->title(__('app.vendor_name')),
            Column::make('purchase_date')->title(__('app.purchase_date')),
            column::make('expected_delivery_date')->title(__('app.expected_delivery_date')),
            column::make('billed_status')->title(__('app.billed_status')),
            Column::make('total')->title(__('app.total')), // make like ₹150,685,930.54
            Column::make('action')->title('Actions'),
        ];
    }

}
