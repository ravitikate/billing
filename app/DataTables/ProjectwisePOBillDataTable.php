<?php
namespace App\DataTables;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProjectwisePOBillDataTable extends DataTable
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
            ->addColumn('purchase_bill_number', function ($row) {
                return '<h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('bills.show', [$row->id]) . '" class="dropdown-item">' . sprintf('Bill#%03d', $row->purchase_bill_number) . '</a></h5>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('bills.show', [$row->id]) . '" class="btn btn-sm ">
                            <i class="fa fa-eye"></i> View
                        </a>';
            })
            ->editColumn('total', function ($row) {
                return '₹ ' . number_format($row->total, 2);
            })
            ->editColumn('status', function ($row) {
                $status = '';

                if ($row->credit_note) {
                    $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.credit-note');
                }
                else {
                    if ($row->status == 'paid') {
                        $status .= ' <i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('app.' . $row->status);
                    }
                    elseif ($row->status == 'partially_paid') {
                        $status .= ' <i class="fa fa-circle mr-1 text-blue f-10"></i>' . __('purchase::modules.purchaseBill.' . $row->status);
                    }
                    elseif ($row->status == 'open') {
                        $status .= ' <i class="fa fa-circle mr-1 text-red f-10"></i>' . __('app.' . $row->status);
                    }
                     elseif ($row->status == 'draft') {
                        $status .= ' <i class="fa fa-circle mr-1 text-blue f-10"></i>' . __('app.' . $row->status);
                    }
                    elseif ($row->status == 'pending') {
                        $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status);
                    }
                    else {
                        $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status);
                    }
                }

                return $status;
            })
            ->rawColumns(['index', 'action','status', 'total', 'purchase_bill_number']);
    }

    public function query(Project $model)
    {
        //    dd($this->projectId);
        return DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('purchase_vendors as pv', 'pv.id', '=', 'po.vendor_id')
            ->select(
                'pb.*',
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
            Column::make('purchase_bill_number')->title(__('app.purchase_bill_number')),
            Column::make('primary_name')->title(__('app.vendor_name')),
            Column::make('bill_date')->title(__('app.bill_date')),
            Column::make('total')->title(__('app.total')),
            Column::make('status')->title(__('app.status')),
            Column::make('action')->title('Actions'),
        ];
    }

}
