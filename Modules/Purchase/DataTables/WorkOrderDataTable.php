<?php
namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseOrder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class WorkOrderDataTable extends BaseDataTable
{
    private $viewOrderPermission;
    private $deleteOrderPermission;
    private $editOrderPermission;
    private $stagePoPermission;
    private $poEdit;

    public function __construct()
    {
        parent::__construct();
        $this->viewOrderPermission   = user()->permission('view_purchase_order');
        $this->deleteOrderPermission = user()->permission('delete_purchase_order');
        $this->editOrderPermission   = user()->permission('edit_purchase_order');
        $this->stagePoPermission     = user()->permission('stage_po');
        $this->poEdit               = user()->permission('po_edit');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">';

                $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                if ($row->type === 'stages') {
                    $action .= '<a href="' . route('purchase-order.show_stages', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
                } else {
                    $action .= '<a href="' . route('purchase-order.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
                }
                //   print_r($this->viewOrderPermission);
                if ( $this->poEdit == 'all' || ($this->poEdit == 'added' && user()->id == $row->added_by)) {
                    $action .= '<a href="' . route('purchase-order.edit_po', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.edit') . '</a>';
                }


                if (
                    $this->viewOrderPermission == 'all'
                    || ($this->viewOrderPermission == 'added' && user()->id == $row->added_by)
                ) {
                    $action .= '<a class="dropdown-item" target="_blank" href="' . route('purchase_order.download', [$row->id, 'view' => true]) . '">
                                    <i class="fa fa-eye mr-2"></i>
                                    ' . trans('app.viewPdf') . '
                                </a>';
                }



                $edit = '<a class="dropdown-item" href="' . route('purchase-order.edit', $row->id) . '" >
                            <i class="fa fa-edit mr-2"></i>
                            ' . trans('app.edit') . '
                        </a>';

                if ($row->status != 'paid' && $row->status != 'canceled'
                    && ($this->editOrderPermission == 'all' || ($this->editOrderPermission == 'added' && $row->added_by == user()->id))
                    && $row->billed_status != 'billed' && $row->delivery_status != "delivered" && $row->validate != "true") {
                    $action .= $edit;
                }


                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })

            ->addColumn('boq_view', function ($row) {
                return '<a href="' . route('purchase-order.show', [$row->id]) . '" class="text-decoration-none"><i class="fa fa-eye mr-2"></i></a>';
            })

            ->addColumn('stages_view', function ($row) {
                if ($row->type === 'stages') {
                    return '<a href="' . route('purchase-order.show_stages', [$row->id]) . '" class="text-decoration-none"><i class="fa fa-eye mr-2"></i></a>';
                } else {
                    return '<a class="dropdown-item openRightModal" href="' . route('projects.stages.import', $row->id) . '" >
                            <i class="fa fa-upload mr-2"></i>
                            ' . trans('app.upload') . '
                        </a>';
                }
                echo 'Row Type: ' . $row->type;

            })

            ->editColumn('purchase_order_number', function ($row) {
                return '<div class="media align-items-center">
                            <div class="media-body">
                        <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('purchase-order.show', [$row->id]) . '">' . ($row->purchase_order_number) . '</a></h5>
                        </div>
                      </div>';
            })
            ->editColumn('primary_name', function ($row) {
                return '<a href="' . route('vendors.show', [$row->vendor_id]) . '" style="color:black;">' . $row->primary_name . '</a>';
            })
            ->editColumn('purchase_date', function ($row) {
                return ! is_null($row->purchase_date) ? $row->purchase_date->translatedFormat($this->company->date_format) : '----';
            })
            ->editColumn('expected_delivery_date', function ($row) {
                return ! is_null($row->expected_delivery_date) ? $row->expected_delivery_date->translatedFormat($this->company->date_format) : '----';
            })
            ->editColumn('total', function ($row) {
                $currencyId = (isset($row->vendor)) ? $row->vendor->currency_id : '';

                return currency_format($row->total, $currencyId);
            })
            ->editColumn('billed_status', function ($row) {
                $status = '';

                if ($row->billed_status == 'unbilled') {
                    $status .= ' <i class="fa fa-circle mr-1 text-red f-10"></i>' . __('purchase::modules.purchaseOrder.' . $row->billed_status);
                } else {
                    $status .= ' <i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('purchase::modules.purchaseOrder.' . $row->billed_status);
                }

                return $status;

            })
            ->editColumn('id', function ($row) {
                return $row->id;
            })
            ->editColumn('del_status', function ($row) {
                $status = '';

                if ($row->delivery_status == 'delivered') {
                    $status .= __('purchase::modules.purchaseOrder.delivered');
                }

                if ($row->delivery_status == 'delivery_failed') {
                    $status .= __('purchase::modules.purchaseOrder.deliveryFailed');
                }

                if ($row->delivery_status == 'in_transaction') {
                    $status .= __('purchase::modules.purchaseOrder.inTransaction');
                }

                if ($row->delivery_status == 'not_started') {
                    $status .= __('purchase::modules.purchaseOrder.notStarted');
                }

                return $status;

            })
            ->editColumn('delivery_status', function ($row) {
                $status = '';

                if ($this->editOrderPermission == 'all' || ($this->editOrderPermission == 'added' && $row->added_by == user()->id)) {
                    if ($row->delivery_status == 'delivered') {
                        $status .= '<i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('purchase::modules.purchaseOrder.' . $row->delivery_status);
                    } else {
                        $status = '<select class="form-control select-picker change-delivery-status" id="delivery-status" data-order-id="' . $row->id . '">';
                        $status .= '<option ';

                        $status .= ' value="delivered" data-content="<i class=\'fa fa-circle mr-2 text-light-green\'></i> ' . __('purchase::modules.purchaseOrder.delivered') . '">' . __('purchase::modules.purchaseOrder.delivered') . '</option>';
                        $status .= '<option ';

                        if ($row->delivery_status == 'delivery_failed') {
                            $status .= 'selected';
                        }

                        $status .= ' value="delivery_failed" data-content="<i class=\'fa fa-circle mr-2 text-red\'></i> ' . __('purchase::modules.purchaseOrder.deliveryFailed') . '">' . __('purchase::modules.purchaseOrder.deliveryFailed') . '</option>';
                        $status .= '<option ';

                        if ($row->delivery_status == 'in_transaction') {
                            $status .= 'selected';
                        }

                        $status .= ' value="in_transaction" data-content="<i class=\'fa fa-circle mr-2 text-yellow\'></i> ' . __('purchase::modules.purchaseOrder.inTransaction') . '">' . __('purchase::modules.purchaseOrder.inTransaction') . '</option>';
                        $status .= '<option ';

                        if ($row->delivery_status == 'not_started') {
                            $status .= 'selected';
                        }

                        $status .= ' value="not_started" data-content="<i class=\'fa fa-circle mr-2 text-dark\'></i> ' . __('purchase::modules.purchaseOrder.notStarted') . '">' . __('purchase::modules.purchaseOrder.notStarted') . '</option>';

                        $status .= '</select>';
                    }

                } else {

                    if ($row->delivery_status == 'delivered') {
                        $class  = 'text-light-green';
                        $status = __('purchase::modules.purchaseOrder.delivered');

                    } else if ($row->delivery_status == 'delivery_failed') {
                        $class  = 'text-red';
                        $status = __('purchase::modules.purchaseOrder.deliveryFailed');

                    } else if ($row->delivery_status == 'in_transaction') {
                        $class  = 'text-yellow';
                        $status = __('purchase::modules.purchaseOrder.inTransaction');

                    } else if ($row->delivery_status == 'not_started') {
                        $class  = 'text-dark';
                        $status = __('purchase::modules.purchaseOrder.notStarted');

                    }

                    $status = '<i class="fa fa-circle mr-1 ' . $class . ' f-10"></i> ' . $status;
                }

                return $status;

            })
            ->addColumn('total_amount', function ($row) {
                $summary = DB::table('purchase_items')
                    ->where('purchase_order_id', $row->id)
                    ->selectRaw('SUM(amount) as total_amount, SUM(retention_amount) as total_retention, SUM(gst_amount) as total_gst')
                    ->first();

                $totalAmount = $summary->total_amount ?? 0;
                $totalGst    = $summary->total_gst ?? 0;
                $total       = $totalAmount + $totalGst;

                return \App\Helper\NumberFormat::formatIndianCurrency($total);
            })
            ->addColumn('retation_amount', function ($row) {
                $summary = DB::table('purchase_items')
                    ->where('purchase_order_id', $row->id)
                    ->selectRaw('SUM(retention) as total_retention')
                    ->first();

                $totalRetention = $summary->total_retention ?? 0;

                return \App\Helper\NumberFormat::formatIndianCurrency($totalRetention);
            })


            ->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '"
                           class="text-decoration-none">
                            ' . ucfirst($row->project_name) . '
                        </a>';
            })

             ->addColumn('subproject', function ($row) {
                return '<a href="' .  route('projects.show', [$row->project_id]). '"
                           class="text-decoration-none">
                            ' . ucfirst($row->subproject) . '
                        </a>';
            })

            ->addColumn('validate', function ($row) {
                $isStatus = filter_var($row->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $isValid  = filter_var($row->validate, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($isValid === false && $isStatus === false) {
                //     $comment = DB::table('po_wo_status')
                //         ->where('po_wo_id', $row->id)
                //         ->orderByDesc('id')
                //         ->value('remark') ?? 'No comment provided.';

                //     return '<a href="javascript:void(0)"
                //      class="text-danger text-decoration-none view-reject"
                //      data-bs-toggle="modal"
                //      data-bs-target="#rejectCommentModal"
                //      data-comment="' . htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') . '">'
                //     . __('app.rejected') . '
                // </a>';

                    return '<a href="javascript:void(0)"
                     class="text-danger text-decoration-none view-reject" onclick="viewRejectComment(' . $row->id . ')">'
                    . __('app.rejected') . '
                </a>';
                } elseif ($isValid === false && $isStatus === true) {
                    $statusClass = ($isValid === true && $isStatus === true) ? 'text-warning' : '';
                    return '<a href="' . route('purchase-order.show', [$row->id]) . '"
                    class="' . $statusClass . '">'
                    . __('app.notvalidate') . '
                </a>';
                } else {
                    $statusText = ($isValid === true && $isStatus === true)
                    ? __('app.validate')
                    : __('app.notvalidate');

                    $statusClass = ($isValid === true && $isStatus === true) ? 'text-success' : '';

                    return '<span class="' . $statusClass . '">'
                        . $statusText . '
                </span>';
                }
            })
            ->addIndexColumn()
            ->smart(false)
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['delivery_status', 'validate','project_name','subproject', 'boq_view', 'stages_view', 'action', 'purchase_order_number', 'billed_status', 'primary_name', 'total', 'del_status', 'total_amount', 'retation_amount']);
    }

    /**
     * @param BankAccount $model
     * @return BankAccount|\Illuminate\Database\Eloquent\Builder
     */
    public function query(PurchaseOrder $model)
    {
        $request = $this->request();

        $vendorId = $request->vendorId;

        $model = $model->with('vendor')
            ->select('p.project_name', 'purchase_orders.validate', 'purchase_vendors.user_id', 't.heading as subproject', 'purchase_orders.project_id', 'purchase_orders.subproject_id', 'purchase_vendors.primary_name', 'purchase_orders.purchase_date',
                'purchase_orders.type', 'purchase_orders.status', 'purchase_orders.billed_status', 'purchase_orders.delivery_status', 'purchase_orders.expected_delivery_date', 'purchase_orders.id',
                'purchase_orders.purchase_order_number', 'purchase_orders.vendor_id', 'purchase_orders.total', 'purchase_orders.send_status', 'purchase_orders.po_number')
            ->join('purchase_vendors', 'purchase_vendors.id', '=', 'purchase_orders.vendor_id')
            ->join('tasks as t', 't.id', '=', 'purchase_orders.subproject_id')
            ->join('projects as p', 'p.id', '=', 'purchase_orders.project_id')
            ->join('project_members as pm', 'pm.project_id', '=', 'purchase_orders.project_id');

        if ($request->searchText != '') {
            $searchText = $request->searchText;
            $model      = $model->where(function ($query) use ($searchText) {
                $query->where('purchase_orders.purchase_order_number', 'like', '%' . $searchText . '%')
                    ->orWhere('purchase_vendors.primary_name', 'like', '%' . $searchText . '%')
                    ->orWhere('p.project_name', 'like', '%' . $searchText . '%')
                    ->orWhere('t.heading', 'like', '%' . $searchText . '%');
            });
        }

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model     = $model->where(DB::raw('DATE(purchase_orders.`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model   = $model->where(DB::raw('DATE(purchase_orders.`purchase_date`)'), '<=', $endDate);
        }

        if ($request->billedStatus != 'all' && ! is_null($request->billedStatus)) {
            $model = $model->where('purchase_orders.billed_status', '=', $request->billedStatus);
        }

        if ($vendorId != 0 && $vendorId != null && $vendorId != 'all') {
            $model->where('purchase_orders.vendor_id', '=', $vendorId);
        }
        $model = $model->where('purchase_orders.order_type', '=', 'work_order');
        //

        $userId = auth()->id();

        if (user()->hasRole('admin')) {
            // $model = $model->whereIn('purchase_bills.status', ['open', 'due']);
        } else
        if ($this->isVendorUser($userId)) {
            $model = $model->where('purchase_vendors.user_id', $userId);
            $model = $model->where('purchase_orders.status', '=', 'true');
            $model = $model->where('purchase_orders.validate', '=', true);
        }else{
            $model = $model->where('pm.user_id', $userId);

        }

        $model = $model->groupBy('purchase_orders.id');

        return $model;
    }
    private function isVendorUser($userId)
    {
        return DB::table('purchase_vendors')->where('user_id', $userId)->exists();
    }
    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->setBuilder('purchase-order-table', 2)
            ->parameters([
                'initComplete'   => 'function () {
                   window.LaravelDataTables["purchase-order-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".select-picker").selectpicker();
                }',
            ])
            ->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $userId = auth()->user()->id;

        // Check if the user is a vendor
        $isVendor = DB::table('purchase_vendors')
            ->where('user_id', $userId)
            ->exists();

        $data = [
            '#'                                                                  => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id')                                                         => ['data' => 'id', 'name' => 'id', 'visible' => false, 'exportable' => false, 'title' => __('app.id')],
            __('app.orderNumber')                                                => ['data' => 'po_number', 'name' => 'purchase_order_number', 'exportable' => true, 'title' => __('app.orderNumber')],
            __('purchase::app.menu.project')                                     => ['data' => 'project_name', 'name' => 'project_name', 'title' => __('purchase::app.menu.project')],
            __('purchase::app.menu.subproject')                                  => ['data' => 'subproject', 'name' => 'subproject', 'title' => __('purchase::app.menu.subproject')],
            __('app.purchaseDate')                                               => ['data' => 'purchase_date', 'name' => 'purchase_date', 'title' => __('app.purchaseDate')],
            __('purchase::modules.purchaseOrder.expectedDate')                   => ['data' => 'expected_delivery_date', 'name' => 'expected_delivery_date', 'title' => __('purchase::modules.purchaseOrder.expectedDate')],
            __('modules.payments.totalAmount')                                   => ['data' => 'total_amount', 'name' => 'total_amount', 'title' => __('modules.payments.totalAmount')],

            __('purchase::modules.purchaseOrder.retation_amount')                => ['data' => 'retation_amount', 'name' => 'retation_amount', 'title' => __('purchase::modules.purchaseOrder.retation_amount')],
            // __('purchase::modules.purchaseOrder.billedStatus') => ['data' => 'billed_status', 'name' => 'billed_status', 'title' => __('purchase::modules.purchaseOrder.billedStatus')],
            // __('modules.payments.BOQ') => ['data' => 'boq_view', 'name' => 'boq_view', 'title' => __('modules.payments.BOQ')],
        ];

        if (! $isVendor) {
            $data = array_slice($data, 0, 5, true)
                + [
                    __('purchase::app.menu.vendor') => [
                        'data' => 'primary_name',
                        'name' => 'primary_name',
                        'title' => __('purchase::app.menu.vendor'),
                    ],
                ]
                + array_slice($data, 5, count($data) - 5, true);

            $data[__('purchase::modules.purchaseOrder.validate')] = [
                'data'       => 'validate',
                'name'       => 'validate',
                'title'      => __('purchase::modules.purchaseOrder.validate'),
                'exportable' => true,
            ];
        }

        // ðŸ‘‡ Add condition here (example: only show if user is admin)
        // if ($this->stagePoPermission == 'all' ) {
        //     $data[__('modules.payments.Final')] = [
        //         'data' => 'stages_view',
        //         'name' => 'stages_view',
        //         'title' => __('modules.payments.Final')
        //     ];
        // }

        // Always append this column
        $data[__('purchase::modules.purchaseOrder.del_status')] = [
            'data'       => 'del_status',
            'visible'    => false,
            'name'       => 'del_status',
            'exportable' => true,
            'title'      => __('purchase::modules.purchaseOrder.deliveryStatus'),
        ];

        $data[__('app.action')] = Column::computed('action', __('app.action'))
            ->exportable(false)
            ->printable(false)
            ->orderable(false)
            ->searchable(false)
            ->addClass('text-right pr-20');

        return $data;
    }

}
