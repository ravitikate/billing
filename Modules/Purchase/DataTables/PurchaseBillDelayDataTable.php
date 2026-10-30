<?php

namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
class PurchaseBillDelayDataTable extends BaseDataTable
{
    private $editBillPermission;
    private $viewBillPermission;

    public function __construct()
    {
        parent::__construct();
        $this->editBillPermission     = user()->permission('edit_bill');
        $this->viewBillPermission     = user()->permission('view_bill');
        $this->resubmitBillPermission = user()->permission('bill_resubmit');
        $this->topsheetBillPermission = user()->permission('topsheet');
    }

    /**
     * Build the DataTable class.
     */
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->orderColumns(['purchase_bill_number', 'pbdBill', 'delay_days'], '-:column $1')
            ->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '" style="color:black;">' . $row->project_name . '</a> <br><a href="javascript:void(0);" style="color:black;">' . $row->subproject . '</a>';
            })
            ->editColumn('date_range', function ($row) {
                    $billDate  = $row->pbdBill ? \Carbon\Carbon::parse($row->pbdBill)->format('d-M-Y') : '';
                    $delayDate = $row->delay_date ? \Carbon\Carbon::parse($row->delay_date)->format('d-M-Y') : '';
                    return $billDate && $delayDate ? $billDate . ' - ' . $delayDate : $billDate;
                })

           ->editColumn('delay_days', function ($row) {
                if ($row->delay_days == 0) {
                    return '<span class="badge bg-success">Within Time</span>';
                }
                return '<span class="text-danger fw-bold">' . $row->delay_days . ' Days</span>';
            })
        ->rawColumns(['delay_days','project_name']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query()
    {
        $request = $this->request();  

        $query = DB::table('purchase_bills_delay as pbd')
            ->join('users as u', 'u.id', '=', 'pbd.user_id')
            ->join('purchase_bills as pb', 'pb.id', '=', 'pbd.bill_id')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('projects as p', 'p.id', '=', 'po.project_id')
            ->leftJoin('tasks as t', 't.id', '=', 'po.subproject_id')
            ->leftJoin('purchase_vendors as pv', 'pv.id', '=', 'pb.purchase_vendor_id')
            ->select(
                'pb.purchase_bill_number',
                'pb.purchase_bill_number as bill_number',
                'pbd.id',
                'pbd.bill_date as pbdBill',
                'pbd.delay_date',
                'pbd.delay_days',
                'pbd.delay_days as delay',
                'u.name as pending_with',
                DB::raw("DATE_FORMAT(pb.bill_date, '%d-%b-%Y') as bill_date"),
                'po.project_id',
                'p.project_name',
                't.heading as subproject',
                'pv.primary_name as vendor_name'
            )
             ->where('pbd.delay_days', '>', 0);

        // Role-based filtering
        if (!user()->hasRole('admin')) {
            if (in_array('vendor', user_roles())) {
                $vendorId = DB::table('purchase_vendors')->where('user_id', user()->id)->value('id');
                if ($vendorId) {
                    $query->where('pb.purchase_vendor_id', $vendorId);
                }
            } else {
                $query->join('project_members as pm', 'pm.project_id', '=', 'po.project_id')
                      ->where('pm.user_id', user()->id);
            }
        }

        // Date filters
        if ($request->startDate) {
            $query->whereDate('pb.bill_date', '>=', date('Y-m-d', strtotime($request->startDate)));
        }
        if ($request->endDate) {
            $query->whereDate('pb.bill_date', '<=', date('Y-m-d', strtotime($request->endDate)));
        }

        // Additional filters
        if ($request->project_id && $request->project_id != 'all') {
            $query->where('po.project_id', $request->project_id);
        }
        if ($request->subproject_id && $request->subproject_id != 'all') {
            $query->where('po.subproject_id', $request->subproject_id);
        }
        if ($request->group_company_id && $request->group_company_id != 'all') {
            $query->where('po.company_group_id', $request->group_company_id);
        }
        if ($request->billing_company_id && $request->billing_company_id != 'all') {
            $query->where('po.billing_company_id', $request->billing_company_id);
        }
        if ($request->vendor_id && $request->vendor_id != 'all') {
            $query->where('pb.purchase_vendor_id', $request->vendor_id);
        }

        // Search filter
        if ($request->searchText) {
            $query->where(function ($q) use ($request) {
                $q->where('pb.purchase_bill_number', 'like', '%' . $request->searchText . '%')
                  ->orWhere('pb.id', 'like', '%' . $request->searchText . '%')
                  ->orWhere('p.project_name', 'like', '%' . $request->searchText . '%')
                  ->orWhere('t.heading', 'like', '%' . $request->searchText . '%')
                  ->orWhere('pv.primary_name', 'like', '%' . $request->searchText . '%')
                  ->orWhere('u.name', 'like', '%' . $request->searchText . '%');
            });
        }

        // Permission-based filter
        if ($this->viewBillPermission === 'added') {
            $query->where('pb.added_by', user()->id);
        }

        return $query->groupBy('pbd.id');
    }

    /**
     * Optional method if you want to use the html builder.
     */




public function html(): HtmlBuilder
{
    $builder = $this->setBuilder('purchasebillsDelay-table', 2)
       ->parameters([
            'dom' => 'Bfrtip', // ✅ add this
            'responsive' => true,
            'scrollX' => true,
            'autoWidth' => false,
            'searching' => true,
            'initComplete' => 'function () {
                window.LaravelDataTables["purchasebillsDelay-table"].buttons().container()
                    .appendTo("#table-actions");
            }',
            'fnDrawCallback' => 'function(oSettings) {
                $(".select-picker").selectpicker();
            }',
        ]);

    // Add Excel export button
    $builder->buttons([
        Button::make('excel')
            ->text('<i class="fa fa-file-export"></i> ' . trans('app.exportExcel'))
    ]);

    return $builder;
}




    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
        {
            $columns = [
                '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#'],
                'ID' => ['data' => 'id', 'name' => 'id', 'visible' => false],
                'Project' => ['data' => 'project_name', 'name' => 'project_name'],
                // 'Subproject' => ['data' => 'subproject', 'name' => 'subproject'],
                'User' => ['data' => 'pending_with', 'name' => 'pending_with'],
                'Bill No.'=>['data'=>'bill_number','name'=>'bill_number'],
                 'Bill Date' => ['data' => 'bill_date', 'name' => 'bill_date'],

                'Range Date' => ['data' => 'date_range', 'name' => 'date_range'],
                'Current Status' => ['data' => 'delay', 'name' => 'delay'],
            ];

            if (!in_array('vendor', user_roles())) {
                $columns = array_slice($columns, 0, 3, true)
                    + ['Vendor' => ['data' => 'vendor_name', 'name' => 'vendor_name']]
                    + array_slice($columns, 3, null, true);
            }

            return $columns;
        }


    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PurchaseBills_' . date('YmdHis');
    }
}
