<?php

namespace Modules\Purchase\DataTables;

use Carbon\Carbon;
use App\Models\User;
use App\DataTables\BaseDataTable;
use Modules\Purchase\Entities\PurchaseVendorCredit;
use Modules\Purchase\Entities\ApprovalItemsBy;
use Modules\Purchase\Entities\PurchaseBillItems;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillApprovalDataTable extends BaseDataTable
{

    public function __construct()
    {
        parent::__construct();
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($this->query()) // Pass query builder, NOT `get()`
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . ($row->id ?? '0') . '"
                name="datatable_ids[]" value="' . ($row->id ?? '0') . '" onclick="dataTableRowCheck(' . ($row->id ?? '0') . ')">';
            })
            ->addColumn('approve_status_id', function ($row) {
                $sessionUserId = Auth::id(); // Get logged-in user ID
                $selectedPending = '';
                $selectedApprove = '';
                $selectedReject = '';

                if ($row->priority_role_user_id == $sessionUserId) { // Fix user check condition
                    if ($row->approve_status_id == 1) {
                        $selectedPending = 'selected';
                    } elseif ($row->status == 2) {
                        return '<span class="badge badge-success">Approved</span>';
                    } elseif ($row->approve_status_id == 3) {
                        return '<span class="badge badge-danger">Rejected</span>';
                    }

                    return '<select class="form-control status-dropdown"
                                data-id="'.$row->id.'"
                                data-project-id="'.$row->projectId.'"
                                data-priority-count="'.$row->priority_count.'"
                                data-approve-status="'.$row->approve_status.'"
                                data-department-id="'.$row->department_id.'">
                            <option value="1" '.$selectedPending.'>Pending</option>
                            <option value="2" '.$selectedApprove.'>Approved</option>
                            <option value="3" '.$selectedReject.'>Rejected</option>
                        </select>'
                        ;
                } else {
                    return '<span style="color: red;">Pending from ' . htmlspecialchars($row->user_name, ENT_QUOTES, 'UTF-8') . '</span>';

                }
            })




            ->addColumn('action', function ($row) {
                return '<div class="task_view">
                            <div class="dropdown">
                                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                                    id="dropdownMenuLink-' . ($row->id ?? '0') . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-options-vertical icons"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . ($row->id ?? '0') . '" tabindex="0">
                                    <a href="' . route('bill.details', $row->id) . '" class="dropdown-item">
                                        <i class="fa fa-file-invoice-dollar mr-2"></i>' . __('app.billDetails') . '
                                    </a>
                                    <a href="' . route('bill.details', $row->id) . '" class="dropdown-item">
                                        <i class="fa fa-file-invoice-dollar mr-2"></i>' . __('app.billDetails') . '
                                    </a>
                                </div>
                            </div>
                        </div>';
            })

            ->addIndexColumn()
            ->smart(false)
            ->setRowId(fn($row) => 'row-' . ($row->id ?? '0'))
            ->rawColumns(['action', 'check','approve_status_id']);
    }

  public function query()
{
    $sessionUserId = Auth::id();

    $query = ApprovalItemsBy::query()
        ->selectRaw("
            projects.id as projectId,
            approval_items_by.department_id,
            approval_items_by.id,
            approval_items_by.approve_status_id as approve_status,
            approval_items_by.status,
            approval_items_by.message,
            approval_items_by.bill_item_id,
            IFNULL(purchase_bill_items.item_name, '') as item_name,
            IFNULL(purchase_bill_items.quantity, 0) as quantity,
            IFNULL(purchase_bill_items.unit_price, 0) as unit_price,
            IFNULL(projects.project_name, '') as project_name,
            IFNULL(purchase_bills.purchase_bill_number, '') as billNumber,
            IFNULL(purchase_vendors.primary_name, '') as primary_name,
             IFNULL(purchase_vendors.id, '') as vendorUserId,
            IFNULL(users.name, '') as user_name,
            IFNULL(priority_role.user_id, '') AS priority_role_user_id,
            IFNULL(priority_count_table.priority_count, 0) AS priority_count
        ")
        ->leftJoin('purchase_bill_items', 'purchase_bill_items.id', '=', 'approval_items_by.bill_item_id')
        ->leftJoin('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bills_id')
        ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'purchase_bills.purchase_bill_number')
        ->leftJoin('projects', 'projects.id', '=', 'purchase_orders.project_id')
        ->leftJoin('purchase_vendors', 'purchase_vendors.id', '=', 'purchase_orders.vendor_id')
        ->leftJoin('priority_role', function ($join) {
            $join->on('priority_role.project_id', '=', 'projects.id')
                 ->on('priority_role.priority_id', '=', 'approval_items_by.approve_status_id');
        })
        ->leftJoin('users', 'users.id', '=', 'priority_role.user_id')
        ->leftJoinSub(
            DB::table('priority_role')
                ->selectRaw('project_id, COUNT(priority_id) as priority_count')
                ->groupBy('project_id'),
            'priority_count_table',
            function ($join) {
                $join->on('priority_count_table.project_id', '=', 'projects.id');
            }
        )
        ->where('purchase_bills.status', 'open');

    // Add condition if the user is an employee
    if (in_array('employee', user_roles())) {
        $query->where('priority_role.user_id', $sessionUserId);
    }
      if (in_array('vendor', user_roles())) {
        $query->where('purchase_vendors.user_id', $sessionUserId);
    }

    return $query;
}






    public function html()
    {
        return $this->setBuilder('vendor-credits-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["vendor-credits-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                  //
                  $(".select-picker").selectpicker();
                }',
            ])
            ->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
    }

    protected function getColumns()
{
    return [
        'check' => [
            'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
            'exportable' => false,
            'orderable' => false,
            'searchable' => false
        ],
        '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
        __('app.id') => ['data' => 'id', 'name' => 'id', 'visible' => false, 'title' => __('app.id')],
        __('app.billNumber') => ['data' => 'billNumber', 'name' => 'purchase_bills.purchase_bill_number', 'title' => __('app.billNumber')],
        __('app.project') => ['data' => 'project_name', 'name' => 'p.project_name', 'title' => __('app.project')],
        __('app.vendor') => ['data' => 'primary_name', 'name' => 'pv.primary_name', 'title' => __('app.vendor')],
        
        __('app.item') => ['data' => 'item_name', 'name' => 'pb.item_name', 'title' => __('app.item')],
        __('app.quantity') => ['data' => 'quantity', 'name' => 'pb.quantity', 'title' => __('app.quantity')],
        __('app.unit_price') => ['data' => 'unit_price', 'name' => 'pb.unit_price', 'title' => __('app.unit_price')],
        __('app.approve_status') => ['data' => 'approve_status_id', 'name' => 'ai.approve_status_id', 'title' => __('app.approve_status')],
        __('app.message') => ['data' => 'message', 'name' => 'ai.message', 'title' => __('app.message')],
        Column::computed('action', __('app.action'))
            ->exportable(false)
            ->printable(false)
            ->orderable(false)
            ->searchable(false)
            ->addClass('text-right pr-20')
    ];
}



}
