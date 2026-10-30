<?php
namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseBill;
use Modules\Purchase\Entities\PurchaseVendor;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class AbstractBillDataTable extends BaseDataTable
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

                $action .= '<a href="' . route('bills.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
                if (($this->topsheetBillPermission == 'all' || $this->topsheetBillPermission == 'added')) {
                    $action .= '<a href="' . route('bills.topSheet', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.topSheet') . '</a>';
                    $action .= '<a class="dropdown-item" target="_blank" href="' . route('generate.new.pdf', [$row->id, 'view' => true]) . '">
                                    <i class="fa fa-eye mr-2"></i>
                                    ' . trans('app.viewPdf') . '
                                </a>';
                }
                if (($this->resubmitBillPermission == 'all' || $this->resubmitBillPermission == 'added')) {
                    $action .= '<a class="dropdown-item" href="' . route('bills.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
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
            // ->addColumn('total', function ($row) {
            //     $currencyId = (isset($row->vendor->currency)) ? $row->vendor->currency->id : '';

            //     return '<div>' . __('app.total') . ': ' . currency_format($row->total, $currencyId) . '<p class="my-0"><span class="text-success mt-1">' . __('app.paid') . ':</span> ' . currency_format($row->amountPaid(), $currencyId) . '</p><span class="text-danger">' . __('app.unpaid') . ':</span> ' . currency_format($row->amountDue($row->purchaseVendor->id), $currencyId) . '</div>';
            // })
            ->addColumn('total', function ($row) {
                // $currencyId = optional(optional($row->vendor)->currency)->id;
                $billId = $row->id;

                // $bill_detail = DB::table('purchase_bills')->find($billId);

                // $curr_bill = DB::table('purchase_bill_items')
                //     ->where('purchase_bills_id', $billId)
                //     ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount, SUM(retention_cost) as total_retention_cost')
                //     ->first();

                // $releaseHoldAmt = DB::table('release_hold_amt')
                //     ->where('release_bill_id', $billId)
                //     ->pluck('hold_amt')
                //     ->sum();

                // $Currentsub_total      = $curr_bill->total_amount ?? 0;
                // $Currenttax_total      = $curr_bill->total_tax_amount ?? 0;
                // $Currentretention_cost = $curr_bill->total_retention_cost ?? 0;
                // $Currenttotal          = $Currentsub_total + $Currenttax_total + $releaseHoldAmt;

                // $AdvanceRecovery = $bill_detail->AdvanceRecovery ?? 0;
                // $HoldBillAmt     = $bill_detail->HoldBillAmt ?? 0;
                // $totalRecoveries = $AdvanceRecovery + $Currentretention_cost + $HoldBillAmt;

                // $instant_debit = DB::table('instant_debit')
                //     ->where('bill_id', $billId)
                //     ->where('bill_status', 2)
                //     ->sum('instantdebitamount') ?? 0;

                // $OtherDebits = collect([
                //     $bill_detail->SafetyDebit ?? 0,
                //     $bill_detail->QualityDebit ?? 0,
                //     $bill_detail->Housekeeping ?? 0,
                //     $bill_detail->PoorPerformance ?? 0,
                //     $bill_detail->OtherDebits ?? 0,
                //     $instant_debit,
                // ])->sum();

                // $TotalDebitAmount = ($bill_detail->Steel ?? 0) + ($bill_detail->Cement ?? 0) + ($bill_detail->Electricity ?? 0) + $OtherDebits;
                // $NetPayableAmount = $Currenttotal - $totalRecoveries - $TotalDebitAmount;

                return '<div class="pointer" onclick="billBreakdown(' . $billId . ')">
                        <p class="my-0"><span class="text-danger">' . __('app.unpaid') . ':</span> ₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($row->netpayableamount - $row->amountPaid())) . '</p>
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
                    } else {
                        $status .= ' <i class="fa fa-circle mr-1 text-yellow f-10"></i>' . __('app.' . $row->status);
                    }
                }

                return $status;
            })
            ->addColumn('abstract', function ($row) {
                $button = '';
                if ($row->status === 'abstract') {
                    $button = '<button type="button" class="btn btn-success btn-xs toggle-btn" onclick="openApprovalPopup(' . $row->id . ')">Approve</button>
                    <button type="button" class="btn btn-danger btn-xs toggle-btn" onclick="openRejectPopup(' . $row->id . ')">Reject</button>';
                } elseif ($row->status === 'abstract_reject') {
                    $button = 'Abstract Reject <br> <a onclick="openRejectReason(' . $row->id . ')">Reject Reason</a>';
                } else {
                    $button = 'Vendor approval is pending';
                }
                return $button;
            })
            ->addColumn('draft', function ($row) {
                $button = '';

                if ($row->status === 'draft') {
                    // Show "Send Invoice" button
                    $button = '<button type="button" class="btn btn-primary btn-xs toggle-btn" onclick="sendInvoiceFromVendor(' . $row->id . ')">Send Invoice</button>';
                } elseif ($row->status === 'abstract_reject') {
                    // Show resubmission link and reject reason
                    $button = 'Abstract Reject - Please RE-Submit <a class="" href="' . route('bills.edit', [$row->id]) . '">' . trans('app.clickHere') . '</a> <br> <a onclick="openRejectReason(' . $row->id . ')">Reject Reason</a>';
                } else {
                    // Default message
                    $button = 'Abstract approval is pending';
                }

                return $button;
            })

            ->addColumn('chat', function ($row) {
                $sessionUserId = Auth::id();
                $projectId     = DB::table('purchase_orders')->where('id', $row->purchase_order_id)->value('project_id');

                // Check if the current user has priority_id == 1 for this project
                $userHasPriority = DB::table('priority_role')
                    ->where('project_id', $projectId)
                    ->where('priority_id', 1)
                    ->where('user_id', user()->id)
                    ->exists();
                // Check if the session user has vendor details
                $vendorDetails = DB::table('purchase_vendors')
                    ->where('user_id', $sessionUserId)
                    ->first();

                $user = DB::table('priority_role')
                    ->where('project_id', $projectId)
                    ->where('priority_id', 1)
                    ->first();

                $userDetails = null;

                if ($user && isset($user->user_id)) {
                    $userDetails = DB::table('users as u')
                        ->join('employee_details as ed', 'ed.user_id', '=', 'u.id')
                        ->join('designations as d', 'd.id', '=', 'ed.designation_id')
                        ->select('u.name as user_name', 'd.name as designation_name')
                        ->where('u.id', $user->user_id)
                        ->first(); // Use first() since you're getting a single user
                }

                if ($vendorDetails) {$chatcount = DB::table('bill_chatting')
                        ->where('bill_id', $row->id)
                        ->where('vendor_status', 'unread')
                        ->count();} else {
                    $chatcount = DB::table('bill_chatting')
                        ->where('bill_id', $row->id)
                        ->where('employee_status', 'unread')
                        ->count();
                }

                if ($row->status === 'abstract' && $userHasPriority) {
                    return '<button class="btn btn-info btnRelative" onclick="openChatPopup(' . $row->id . ', \'' . $row->bill_number . '\')"><i class="fa fa-comments" title="Chating"></i><span class="chat-count">' . $chatcount . '</span></button>';
                } elseif ($vendorDetails) {
                    return '<button class="btn btn-info btnRelative" onclick="openChatPopup(' . $row->id . ', \'' . $row->bill_number . '\')"><i class="fa fa-comments" title="Chating"></i> <span class="chat-count">' . $chatcount . '</span></button>';
                } else {
                    if ($userDetails) {
                        return '<button class="btn btn-info disabled" title="No permission"><i class="fa fa-comments"></i></button>';
                    } else {
                        return '<span class="text-danger">No</span>';
                    }
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
            ->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '" style="color:black;">' . $row->project_name . '</a> <br><a href="' . url('account/tasks', [$row->subproject_id]) . '?tab=project" style="color:black;">' . $row->subproject . '</a>';
            })

            ->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['action', 'vendor', 'project_name', 'status', 'total', 'purchase_bill_number', 'abstract', 'order_type', 'chat', 'draft'])
            ->orderColumns(['purchase_bill_number', 'order_type', 'bill_date', 'total', 'status'], '-:column $1');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(): QueryBuilder
    {

        $request = $this->request();
        $userId  = user()->id;

        $vendor = PurchaseVendor::where('user_id', $userId)->first();

        $model = PurchaseBill::with('purchaseVendor', 'vendor.currency', 'paymentBill', 'purchasePaymentBills')
            ->join('purchase_orders', 'purchase_bills.purchase_order_id', '=', 'purchase_orders.id')
            ->join('projects', 'projects.id', '=', 'purchase_orders.project_id')
        // ->join('tasks', 'tasks.project_id', '=', 'projects.id')
            ->join('tasks', 'tasks.id', '=', 'purchase_orders.subproject_id')
            ->select(
                'purchase_bills.*',
                'projects.id as project_id',
                'purchase_orders.order_type',
                'projects.project_name',
                'tasks.heading as subproject',
                'tasks.id as subproject_id'
            )
            ->whereIn('purchase_bills.status', ['abstract', 'draft', 'abstract_reject'])
            ->groupBy('purchase_bills.id');
        if (user()->hasRole('admin')) {
            $model = $model->where('purchase_bills.status', 'abstract');
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

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model     = $model->where(DB::raw('DATE(purchase_bills.`bill_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model   = $model->where(DB::raw('DATE(purchase_bills.`bill_date`)'), '<=', $endDate);
        }

        if ($request->status != 'all' && ! is_null($request->status)) {
            $model = $model->where('purchase_bills.status', '=', $request->status);
        }

        if ($request->vendor_id != 'all' && ! is_null($request->vendor_id)) {
            $model = $model->where('purchase_bills.purchase_vendor_id', '=', $request->vendor_id);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('purchase_bills.purchase_bill_number', 'like', '%' . request('searchText') . '%')
                    ->orWhere('purchase_bills.bill_date', 'like', '%' . request('searchText') . '%')
                    ->orWhere('purchase_bills.id', 'like', '%' . request('searchText') . '%')
                    ->orWhere('purchase_bills.total', 'like', '%' . request('searchText') . '%')
                    ->orWhere(function ($query) {
                        $query->whereHas('purchaseVendor', function ($q) {
                            $q->where('primary_name', 'like', '%' . request('searchText') . '%');
                        });
                    });
            });
        }

        $model->when($request->project_id, function ($query) use ($request) {
            $query->where('purchase_orders.project_id', $request->project_id);
        });

        $model->when($request->subproject_id, function ($query) use ($request) {
            $query->where('purchase_orders.subproject_id', $request->subproject_id);
        });

        $model->when($request->group_company_id, function ($query) use ($request) {
            $query->where('purchase_orders.company_group_id', $request->group_company_id);
        });

        $model->when($request->billing_company_id, function ($query) use ($request) {
            $query->where('purchase_orders.billing_company_id', $request->billing_company_id);
        });

        if ($this->viewBillPermission == 'added') {
            $model = $model->where('purchase_bills.added_by', user()->id);
        }
        return $model;
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
    public function getColumns(): array
    {
        $columns = [
            '#'                               => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => true, 'title' => '#'],
            __('app.id')                      => ['data' => 'id', 'name' => 'id', 'title' => __('app.id'), 'visible' => false],

            Column::make('purchase_bill_number')
                ->title('Bill Number')
                ->data('purchase_bill_number')->exportable(false),

            // __('purchase::app.menu.po_type')    => ['data' => 'order_type', 'name' => 'order_type', 'exportable' => false],
            __('purchase::app.menu.project')  => ['data' => 'project_name', 'name' => 'project_name', 'title' => __('purchase::app.menu.project')],
            // __('purchase::app.menu.subproject') => ['data' => 'subproject', 'name' => 'subproject', 'title' => __('purchase::app.menu.subproject')],
            __('purchase::app.menu.billDate') => ['data' => 'bill_date', 'name' => 'bill_date', 'exportable' => false],
            __('app.total')                   => ['data' => 'total', 'name' => 'total', 'exportable' => false],
        ];

        if (!in_array('vendor', user_roles())) {
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

        // Check user role before adding 'abstract' column
        $sessionUserId = Auth::id();
        $checkUser     = DB::table('priority_role')->where('user_id', $sessionUserId)->first();

        if ($checkUser) {
            $columns[] = Column::computed('abstract', __('app.status'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center');
        } else {
            $columns[] = Column::computed('draft', __('app.status'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center');
        }

        // Always add chat and action columns
        $columns[] = Column::computed('chat', __('app.chat'))
            ->exportable(false)
            ->printable(false)
            ->orderable(false)
            ->searchable(false)
            ->addClass('text-center');

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
