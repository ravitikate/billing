<?php
namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseBill;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class PurchaseBillDataTable extends BaseDataTable
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
        $this->add_vendor_payment   = user()->permission('add_vendor_payment');
    }

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">

                <div class="dropdown">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                if ($this->viewBillPermission == 'all' || $this->viewBillPermission == 'added' && user()->id == $row->added_by) {
                    $action .= '<a href="' . route('bills.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
                }
                if (($this->topsheetBillPermission == 'all' || $this->topsheetBillPermission == 'added')) {
                    $action .= '<a href="' . route('bills.topSheet', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.topSheet') . '</a>';
                    $action .= '<a class="dropdown-item" target="_blank" href="' . route('generate.new.pdf', [$row->id, 'view' => true]) . '">
                                    <i class="fa fa-eye mr-2"></i>
                                    ' . trans('app.viewPdf') . '
                                </a>';
                }
                if ($row->status != 'paid' && $row->status != 'open' && $row->status != 'pending') {
                    $action .= '<a class="dropdown-item openRightModal" href="' . route('bills.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
                }

                // if ($row->status == 'paid' && $row->credit_note == 0) {
                //     $action .= '<a class="dropdown-item openRightModal" href="' . route('vendor-credits.creates', [$row->id]) . '">
                //     <i class="fa fa-edit mr-2"></i>
                //         ' . trans('purchase::app.menu.addCredit') . '
                //         </a>';
                // }

               if ($this->add_vendor_payment == 'all' ||
        ($this->add_vendor_payment == 'added' && user()->id == $row->added_by)) {

        if ($row->status == 'open' || $row->status == 'partially_paid') {
            $netAmount = $row->netpayableamount;

            $action .= '<a class="dropdown-item" href="' . route('vendor-payments.create') . '?bill=' . $row->id . '" data-toggle="tooltip"  data-bill-id="' . $row->id . '">
            <i class="fa fa-plus mr-2"></i>
            ' . trans('purchase::app.menu.addPayment') . '
            </a>';
        }
    }

                $action .= '</div>
                        </div>
                    </div>';

                return $action;
            })

            ->addColumn('bill_date', function ($row) {
                return $row->bill_date->timezone($this->company->timezone)->translatedFormat($this->company->date_format);
            })
            ->addColumn('vendor', function ($row) {
                return '<a href="' . route('vendors.show', [$row->purchaseVendor->id]) . '" style="color:black;">' . $row->purchaseVendor->primary_name . '</a>';
            })
            ->addColumn('purchase_bill_number', function ($row) {
                return '<h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('bills.show', [$row->id]) . '" class="dropdown-item">' . $row->purchase_bill_number . '</a></h5>';
            })
            ->addColumn('total', function ($row) {
                // $NetPayableAmount = $this->calculateNetPayable($row);

                return '<div class="pointer" onclick="billBreakdown(' . $row->id . ')">
                        <p class="my-0"><span class="text-danger">' . __('app.due') . ':</span> ₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($row->netpayableamount - $row->amountPaid())) . '</p>
                        <p class="my-0"><span class="text-success">' . __('app.paid') . ':</span> ₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($row->amountPaid())) . '</p>
                    </div>';
            })

            ->addColumn('status', function ($row) {
                $status = '';

                if ($row->credit_note) {
                    $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.credit-note');
                } else {
                    if ($row->status == 'paid') {
                        $status .= ' <i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('app.' . $row->status);
                    } elseif ($row->status == 'partially_paid') {
                        $status .= ' <i class="fa fa-circle mr-1 text-blue f-10"></i>' . __('purchase::modules.purchaseBill.' . $row->status);
                    } elseif ($row->status == 'open') {
                        $status .= ' <i class="fa fa-circle mr-1 text-red f-10"></i>' . __('app.' . $row->status);
                    } elseif ($row->status == 'draft') {
                        $status .= ' <i class="fa fa-circle mr-1 text-blue f-10"></i>' . __('app.' . $row->status);
                    } elseif ($row->status == 'pending') {
                        $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status);
                    } else {
                        $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status);
                    }
                }

                return $status;
            })
            ->addColumn('draft', function ($row) {
                if ($row->status === 'draft') {
                    return '<button class="btn btn-success toggle-btn" onclick="openApprovalPopup(' . $row->id . ')">Submit Bill</button>';
                }
            })
            ->addColumn('order_type', function ($row) {
                if ($row->order_type === 'purchase_order') {
                    return 'Purchase Order';
                } elseif ($row->order_type === 'work_order') {
                    return 'Work Order';
                } else {
                    return '';
                }

            })
            ->addColumn('total_amount', function ($row) {
                $summary = DB::table('purchase_bill_items')
                    ->where('purchase_bills_id', $row->id)
                    ->selectRaw('SUM(amount) as total_amount,
                        SUM(tax_amount) as total_gst')
                    ->first();

                $totalAmount = $summary->total_amount ?? 0;
                $totalGst    = $summary->total_gst ?? 0;
                $total       = $totalAmount + $totalGst;

                return \App\Helper\NumberFormat::formatIndianCurrency($total);
            })
            ->addColumn('retation_amount', function ($row) {
                $summary = DB::table('purchase_bill_items')
                    ->where('purchase_bills_id', $row->id)
                    ->selectRaw('SUM(retention_cost) as total_retention')
                    ->first();

                $totalRetention = $summary->total_retention ?? 0;

                return \App\Helper\NumberFormat::formatIndianCurrency($totalRetention);
            })
            ->addColumn('payment_status', function ($row) {
                $action = '';

                // Check if user has permission to add vendor payment
                if ($this->add_vendor_payment == 'all' ||
                    ($this->add_vendor_payment == 'added' && user()->id == $row->added_by)) {

                    if ($row->status == 'open' || $row->status == 'partially_paid') {
                        $action .= '<a class="btn btn-info btn-xs" href="' . route('vendor-payments.create') . '?bill=' . $row->id . '"
                            data-toggle="tooltip" data-bill-id="' . $row->id . '">
                            <i class="fa fa-plus mr-2"></i>
                            ' . trans('app.makePayment') . '
                        </a>';
                    }
                }

                return $action;
            })
            ->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '" style="color:black;">' . $row->project_name . '</a> <br><a href="javascript:void(0);" style="color:black;">' . $row->subproject . '</a>';
            })
            ->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['action', 'vendor', 'status', 'project_name', 'payment_status', 'total', 'draft', 'purchase_bill_number', 'order_type', 'total_amount', 'retation_amount'])
            ->orderColumns(['purchase_bill_number', 'order_type', 'bill_date', 'total', 'total_amount', 'retation_amount', 'status'], '-:column $1');
    }

    

    /**
     * Get the query source of dataTable.
     */
    public function query(): QueryBuilder
    {
        $request = $this->request();

        $model = PurchaseBill::with('purchaseVendor', 'vendor.currency', 'paymentBill', 'purchasePaymentBills')
            ->join('purchase_orders', 'purchase_bills.purchase_order_id', '=', 'purchase_orders.id')
            ->join('projects', 'projects.id', '=', 'purchase_orders.project_id')
            ->join('tasks', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'purchase_bills.*',
                'purchase_orders.project_id',
                'purchase_orders.subproject_id',
                'purchase_orders.company_group_id',
                'purchase_orders.billing_company_id',
                'purchase_orders.order_type',
                'projects.project_name',
                'tasks.heading as subproject'
            );

        // Date filters
        if (($request->startDate)) {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model->whereDate('purchase_bills.bill_date', '>=', $startDate);
        }

        if (($request->endDate)) {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model->whereDate('purchase_bills.bill_date', '<=', $endDate);
        }

        // Status filters

        if (($request->vendor_id)) {

            if ($request->tab == 'history') {
                $model->whereIn('purchase_bills.status', ['open', 'partially_paid', 'due', 'draft', 'open', 'paid', 'abstract', 'pending', 'partially_paid']);
            }
            if ($request->tab == 'bills') {
                $model->where('purchase_bills.status', 'pending');
            }

        } else {

             $statusFilters = ['due', 'draft', 'open', 'paid', 'abstract', 'pending', 'partially_paid'];
            if (! empty($request->status) && in_array($request->status, $statusFilters)) {
                $model->where('purchase_bills.status', $request->status);
            } else {
                $model->whereIn('purchase_bills.status', ['paid', 'open']);
            }
        }



        if (user()->hasRole('admin')) {
            // $model = $model->whereIn('purchase_bills.status', ['open', 'due']);
        } else {
            if (in_array('vendor', user_roles())) {
                $vendorId = DB::table('purchase_vendors')->where('user_id', user()->id)->value('id');
                if ($vendorId) {
                    $model = $model->where('purchase_bills.purchase_vendor_id', $vendorId);
                }
            } else {
                $model = $model
                    ->join('project_members', 'project_members.project_id', '=', 'projects.id')
                    ->where('project_members.user_id', user()->id);
            }

        }

        // Additional filters
        if ($request->project_id) {
            $model->where('purchase_orders.project_id', $request->project_id);
        }

        if ($request->subproject_id) {
            $model->where('purchase_orders.subproject_id', $request->subproject_id);
        }
        if ($request->group_company_id) {
            $model->where('purchase_orders.company_group_id', $request->group_company_id);
        }

        if ($request->billing_company_id) {
            $model->where('purchase_orders.billing_company_id', $request->billing_company_id);
        }

        if ($request->vendor_id !== 'all' && ! is_null($request->vendor_id)) {
            $model->where('purchase_bills.purchase_vendor_id', $request->vendor_id);
        }

        // Search filter
        if (! empty($request->searchText)) {
            $model->where(function ($query) use ($request) {
                $query->where('purchase_bills.purchase_bill_number', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.bill_date', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.id', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.total', 'like', '%' . $request->searchText . '%')
                    ->orWhereHas('purchaseVendor', function ($q) use ($request) {
                        $q->where('primary_name', 'like', '%' . $request->searchText . '%');
                    });
            });
        }

        // Permission filter
        if ($this->viewBillPermission === 'added') {
            $model->where('purchase_bills.added_by', user()->id);
        }

        return $model->groupBy('purchase_bills.id');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return parent::setBuilder('purchasebills-table', 1)
            ->parameters([
                'initComplete'   => 'function () {
                    window.LaravelDataTables["purchasebills-table"].buttons().container()
                     .appendTo( "#table-actions")
                 }',
                'fnDrawCallback' => 'function( oSettings ) {
                   $(".select-picker").selectpicker();
                 }',
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
{
    $isVendor = in_array('vendor', user_roles());

    $columns = [
        '#' => [
            'data' => 'DT_RowIndex',
            'orderable' => false,
            'searchable' => false,
            'visible' => true,
            'title' => '#'
        ],

        __('app.id') => [
            'data' => 'id',
            'name' => 'id',
            'title' => __('app.id'),
            'visible' => false
        ],

        __('purchase::app.menu.billNumber') => [
            'data' => 'purchase_bill_number',
            'name' => 'purchase_bill_number',
            'exportable' => false
        ],

        __('purchase::app.menu.project') => [
            'data' => 'project_name',
            'name' => 'project_name',
            'title' => __('purchase::app.menu.project')
        ],

        __('purchase::app.menu.billDate') => [
            'data' => 'bill_date',
            'name' => 'bill_date',
            'exportable' => false
        ],

        __('app.total') => [
            'data' => 'total',
            'name' => 'total',
            'exportable' => false
        ],

        __('purchase::modules.purchaseOrder.retation_amount') => [
            'data' => 'retation_amount',
            'name' => 'retation_amount',
            'title' => __('purchase::modules.purchaseOrder.retation_amount')
        ],

        __('app.status') => [
            'data' => 'status',
            'name' => 'status',
            'exportable' => false
        ],
    ];

    if (!$isVendor) {
        $columns = array_slice($columns, 0, 4, true)
            + [
                __('purchase::app.menu.vendor') => [
                    'data' => 'vendor',
                    'name' => 'purchaseVendor.primary_name',
                    'exportable' => false,
                ],
            ]
            + array_slice($columns, 4, null, true);
    }

    /** ❌ Hide payment_status for Vendor */
    if (!$isVendor) {
        $columns[__('app.payment')] = [
            'data' => 'payment_status',
            'name' => 'payment_status',
            'exportable' => false
        ];
    }

    /** Action column */
    $columns[] = Column::computed('action', __('app.action'))
        ->exportable(false)
        ->printable(false)
        ->orderable(false)
        ->searchable(false)
        ->addClass('text-right pr-20');

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
