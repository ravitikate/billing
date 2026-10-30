<?php
namespace Modules\Purchase\DataTables;

use App\DataTables\BaseDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseBill;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class PurchaseBillApprovalDataTable extends BaseDataTable
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
            ->addColumn('check', function ($row) {

                $sessionUserId   = Auth::id(); // Get logged-in user ID
                $selectedPending = ($row->approve_status == 1) ? 'selected' : '';
                $selectedApprove = '';
                $selectedReject  = '';

                $debugInfo = '';
                if ($row->priority_role_user_id == $sessionUserId) {
                    return '<input type="checkbox" class="select-table-row-new" id="datatable-row-new-' . ($row->id ?? '0') . '"
                name="datatable_ids[]" value="' . ($row->id ?? '0') . '" onclick="dataTableRowCheckNew(' . ($row->id ?? '0') . ')">';

                } else {

                }

            })
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

                // <a href="' . route('bills.details', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.details') . '</a>';
                if (($this->topsheetBillPermission == 'all' || $this->topsheetBillPermission == 'added')) {
                    $action .= '<a href="' . route('bills.topSheet', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.topSheet') . '</a>';
                    $action .= '<a class="dropdown-item" target="_blank" href="' . route('generate.new.pdf', [$row->id, 'view' => true]) . '">
                                    <i class="fa fa-eye mr-2"></i>
                                    ' . trans('app.viewPdf') . '
                                </a>';    
                }
                if ($this->resubmitBillPermission == 'all' || ($this->resubmitBillPermission == 'added' && user()->id == $row->added_by)) {
                    $currentBillUser = DB::table('approval_bills_by')
                        ->where('bill_id', $row->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $priorityUser = DB::table('priority_role')
                        ->where('priority_id', 1)
                        ->where('project_id', $row->project_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $abcsecond = DB::table('approval_bill_details')
                        ->where('bill_id', $row->id)
                        ->where('user_id', $priorityUser->user_id)
                        ->orderBy('id', 'desc')
                        ->skip(1)
                        ->first();

                    if ($currentBillUser && $abcsecond && $currentBillUser->approval_status === 1 && $abcsecond->approval_status === 3) {
                        $action .= '<a class="dropdown-item openRightModal" href="' . route('bills.edit', [$row->id]) . '">
                                            <i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '
                                        </a>';
                    }
                }

                $action .= '</div>
                            </div>
                        </div>';

                return $action;
            })
            ->addColumn('approve_statuss', function ($row) {
                $sessionUserId = Auth::id();

                // Get logged-in user ID
                $selectedPending = ($row->approve_status == 1) ? 'selected' : '';
                $selectedApprove = '';
                $selectedReject  = '';

                $lastEntry = DB::table('priority_role')
                    ->where('user_id', $sessionUserId)
                    ->where('project_id', $row->project_id)
                    ->orderByDesc('id')
                    ->first();
                if ($lastEntry) {
                    $currentApproval = $row->approve_status;
                    $userPriority    = $lastEntry->priority_id;

                    if ($lastEntry->priority_id >= $currentApproval) {
                        return '<select class="form-control status-dropdown"
                        data-id="' . $row->abId . '"
                        data-project-id="' . ($row->project_id ?? '') . '"
                        data-priority-count="' . $row->priority_count . '"
                        data-approve-status="' . $row->approve_status . '"
                        data-department-id="' . ($row->department_id ?? '') . '">
                    <option value="1" ' . $selectedPending . '>Pending</option>
                    <option value="2" ' . $selectedApprove . '>Approved</option>
                    <option value="3" ' . $selectedReject . '>Rejected</option>
                </select>';
                    }
                }
                return ''; // Or some other fallback like status text
            })

            ->addColumn('pending_status', function ($row) {

                $users = DB::table('purchase_bills as pb')
                    ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
                    ->join('priority_role as pr', 'pr.project_id', '=', 'po.project_id')
                    ->join('users as u', 'u.id', '=', 'pr.user_id')
                    ->where('pb.id', $row->id)
                    ->select('u.name', 'u.id as user_id')
                    ->get();

                $currentBillUser = DB::table('approval_bills_by')
                    ->where('bill_id', $row->id)
                    ->orderBy('id', 'desc') // Get latest
                    ->first();

                $html = '';

                foreach ($users as $user) {
                    // Get latest approval_bill_details for the user
                    $abc = DB::table('approval_bill_details')
                        ->where('bill_id', $row->id)
                        ->where('user_id', $user->user_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $abcsecond = DB::table('approval_bill_details')
                        ->where('bill_id', $row->id)
                        ->where('user_id', $user->user_id)
                        ->orderBy('id', 'desc')
                        ->skip(1)
                        ->first();

                    // Get current bill's approval status
                    $priorityIdUser = DB::table('approval_bills_by as ab')
                        ->where('ab.bill_id', $row->id)
                        ->value('ab.approval_status');

                    // Get user who has this priority ID
                    $priorityUser = DB::table('priority_role')
                        ->where('priority_id', $priorityIdUser)
                        ->where('project_id', $row->project_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    $status = 'Pending'; // Default status

                    // Determine status based on approval_bill_details
                    if ($abc) {
                        if ($abc->bypass === 'bypass') {
                            $status = 'Skip';
                        } elseif ($abc->approval_status == 2) {
                            $status = 'Approved';
                        } elseif ($abc->approval_status == 3) {
                            $status = 'Rejected';
                        }
                    }

                    switch ($status) {
                        case 'Skip':
                            $icon = '<i class="fa fa-forward" style="color: orange;" title="Skipped"></i>';
                            break;
                        case 'Approved':
                            $icon = '<i class="fa fa-check-circle" style="color: green;" title="Approved"></i>';
                            break;
                        case 'Rejected':
                            $icon = '<i class="fa fa-times-circle" style="color: red;" title="Rejected"></i>';
                            break;
                        default:
                            $icon = '<i class="fa fa-hourglass-half" style="color: gray;" title="Pending"></i>';
                    }

                    $u = $user->name;

                    if ($priorityUser && $user->user_id == $priorityUser->user_id) {
                        if ($abcsecond && $abcsecond->approval_status === 3) {
                            $icon = '<i class="fa fa-times-circle" style="color: red;" title="Rejected"></i>';
                        } else {
                            $icon = '<i class="fa fa-hourglass-half" style="color: gray;" title="Pending or Not Found"></i>';
                        }

                        $html .= '<b><p class="user-status"> ' . $icon . ' <span class="user-name">' . $u . '</span> </p></b>';
                    } else {
                        $html .= '<p class="user-status"> ' . $icon . ' <span class="user-name">' . $u . '</span> </p>';
                    }
                }

                return ($html);
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
                $currencyId = optional(optional($row->vendor)->currency)->id;
                $billId     = $row->id;

                $bill_detail = DB::table('purchase_bills')->find($billId);

                $curr_bill = DB::table('purchase_bill_items')
                    ->where('purchase_bills_id', $billId)
                    ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount, SUM(retention_cost) as total_retention_cost')
                    ->first();

                $releaseHoldAmt = DB::table('release_hold_amt')
                    ->where('release_bill_id', $billId)
                    ->pluck('hold_amt')
                    ->sum();

                $Currentsub_total      = $curr_bill->total_amount ?? 0;
                $Currenttax_total      = $curr_bill->total_tax_amount ?? 0;
                $Currentretention_cost = $curr_bill->total_retention_cost ?? 0;
                $Currenttotal          = $Currentsub_total + $Currenttax_total + $releaseHoldAmt;

                $AdvanceRecovery = $bill_detail->AdvanceRecovery ?? 0;
                $HoldBillAmt     = $bill_detail->HoldBillAmt ?? 0;
                $totalRecoveries = $AdvanceRecovery + $Currentretention_cost + $HoldBillAmt;

                $instant_debit = DB::table('instant_debit')
                    ->where('bill_id', $billId)
                    ->where('bill_status', 2)
                    ->sum('instantdebitamount') ?? 0;

                $OtherDebits = collect([
                    $bill_detail->SafetyDebit ?? 0,
                    $bill_detail->QualityDebit ?? 0,
                    $bill_detail->Housekeeping ?? 0,
                    $bill_detail->PoorPerformance ?? 0,
                    $bill_detail->OtherDebits ?? 0,
                    $instant_debit,
                ])->sum();

                $TotalDebitAmount = ($bill_detail->Steel ?? 0) + ($bill_detail->Cement ?? 0) + ($bill_detail->Electricity ?? 0) + $OtherDebits;
                $NetPayableAmount = $Currenttotal - $totalRecoveries - $TotalDebitAmount;

                return '<div class="pointer" onclick="billBreakdown(' . $billId . ')">
                        <p class="my-0"><span class="text-danger">' . __('app.unpaid') . ':</span> ₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($NetPayableAmount)) . '</p>
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
            ->addColumn('chat', function ($row) {
                $sessionUserId   = Auth::id(); // Get logged-in user ID
                $selectedPending = ($row->approve_status == 1) ? 'selected' : '';
                $selectedApprove = '';
                $selectedReject  = '';

                $vendorDetails = DB::table('purchase_vendors')
                    ->where('user_id', $sessionUserId)
                    ->first();

                if ($vendorDetails) {$chatcount = DB::table('bill_chatting')
                        ->where('bill_id', $row->id)
                        ->where('vendor_status', 'unread')
                        ->count();} else {
                    $chatcount = DB::table('bill_chatting')
                        ->where('bill_id', $row->id)
                        ->where('employee_status', 'unread')
                        ->count();
                }

                $debugInfo = '';
                // if ($row->priority_role_user_id == $sessionUserId || $vendorDetails) {
                return '<button class="btn btn-info btnRelative" onclick="openChatPopup(' . $row->id . ', \'' . $row->bill_number . '\')"><i class="fa fa-comments" title="Chating"></i> <span class="chat-count">' . $chatcount . '</span></button>';
                // } elseif ($row->approve_status == 1 && $row->actual_status == 3) {
                //     return '<button class="btn btn-info disabled" title="No permission"><i class="fa fa-comments"></i></button>';

                // } else {
                //     return '<button class="btn btn-info disabled" title="No permission"><i class="fa fa-comments"></i></button>';

                // }

            })
            ->addColumn('debit_details', function ($row) {
                return '<button type="button"  class="btn btn-success btn-xs toggle-btn" onclick="update_debit_details(' . $row->id . ')">Debit</button>';
            })
             ->addColumn('project_name', function ($row) {
                return '<a href="' . route('projects.show', [$row->project_id]) . '" style="color:black;">' . $row->project_name . '</a> <br><a href="javascript:void(0);" style="color:black;">' . $row->subproject . '</a>';
            })

            ->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['action', 'check', 'approve_statuss', 'vendor', 'status', 'total', 'draft', 'purchase_bill_number', 'order_type', 'chat', 'pending_status', 'debit_details','project_name'])
            ->orderColumns(['purchase_bill_number', 'order_type', 'bill_date', 'total', 'status','project_name'], '-:column $1');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(): QueryBuilder
    {
        $request = $this->request();
        // \DB::enableQueryLog();
        $model = PurchaseBill::with('purchaseVendor', 'vendor.currency', 'paymentBill', 'purchasePaymentBills')
            ->join('purchase_orders', 'purchase_bills.purchase_order_id', '=', 'purchase_orders.id')
            ->join('projects', 'projects.id', '=', 'purchase_orders.project_id')
            ->join('tasks', 'tasks.id', '=', 'purchase_orders.subproject_id')
            ->join('approval_bills_by', 'approval_bills_by.bill_id', '=', 'purchase_bills.id')
            ->leftJoin('priority_role', function ($join) {
                $join->on('priority_role.project_id', '=', 'projects.id')
                    ->on('priority_role.priority_id', '=', 'approval_bills_by.approval_status');
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
            ->where('purchase_bills.status', 'pending')
            ->select(
                'purchase_bills.*',
                'purchase_orders.order_type',
                'projects.id as project_id',
                'projects.project_name',
                'tasks.heading as subproject',
                'approval_bills_by.id as abId',
                'approval_bills_by.approval_status as approve_status',
                'approval_bills_by.status as abStatus',
                'purchase_bills.status as pbStatus',
                'approval_bills_by.actual_status as actual_status',
                'approval_bills_by.message',
                'approval_bills_by.bill_id',
                DB::raw('IFNULL(users.name, "") as user_name'),
                DB::raw('IFNULL(priority_role.user_id, "") AS priority_role_user_id'),
                DB::raw('IFNULL(priority_count_table.priority_count, 0) AS priority_count'),
                DB::raw("
            CASE
                WHEN priority_role.user_id = " . Auth::id() . "
                THEN 1 ELSE 0
            END as is_my_turn
        ")
            );

        // Filter by vendor if the user has the vendor role

        if (user()->hasRole('admin')) {
            $model = $model->where('purchase_bills.status', 'pending');
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

        // Date filter
        if (! empty($request->startDate)) {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model     = $model->whereDate('purchase_bills.bill_date', '>=', $startDate);
        }

        if (! empty($request->endDate)) {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model   = $model->whereDate('purchase_bills.bill_date', '<=', $endDate);
        }

        // Status filter
        if ($request->status != 'all' && ! is_null($request->status)) {
            $model = $model->where('purchase_bills.status', $request->status);
        }

        // Vendor filter
        if ($request->vendor_id != 'all' && ! is_null($request->vendor_id)) {
            $model = $model->where('purchase_bills.purchase_vendor_id', $request->vendor_id);
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

        // Search filter
        if (! empty($request->searchText)) {
            $model->where(function ($query) use ($request) {
                $query->where('purchase_bills.purchase_bill_number', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.bill_date', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.id', 'like', '%' . $request->searchText . '%')
                    ->orWhere('purchase_bills.total', 'like', '%' . $request->searchText . '%')
                    ->orWhere(function ($query) use ($request) {
                        $query->whereHas('purchaseVendor', function ($q) use ($request) {
                            $q->where('primary_name', 'like', '%' . $request->searchText . '%');
                        });
                    });
            });
        }

        // Permission-based filter
        if ($this->viewBillPermission == 'added') {
            $model = $model->where('purchase_bills.added_by', user()->id);
        }
        $model = $model->groupBy('purchase_bills.id');
//     \Log::info('SQL Query: ' . $model->toSql());
// \Log::info('Query Bindings:', $model->getBindings());

        return $model;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return parent::setBuilder('purchasebills-table', 1)
            ->parameters([
                'order'          => [[array_search('MyTurn', array_keys($this->getColumns())), 'desc']],
                'responsive'     => true,
                'scrollX'        => true,
                'autoWidth'      => false,
                'initComplete'   => 'function () {
                window.LaravelDataTables["purchasebills-table"].buttons().container()
                    .appendTo("#table-actions");
            }',
                'fnDrawCallback' => 'function(oSettings) {
                $(".select-picker").selectpicker();
            }',
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            'check'                             => [
                'title'      => '<input type="checkbox" name="select_all_table_new" id="select-all-table-new" onclick="selectAllTableNew(this)">',
                'exportable' => false,
                'orderable'  => false,
                'searchable' => false,
            ],
            '#'                                 => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => true, 'title' => '#'],
            // __('app.id') => ['data' => 'id', 'name' => 'id', 'title' => __('app.id'), 'visible' => false],
            __('purchase::app.menu.billNumber') => ['data' => 'purchase_bill_number', 'name' => 'purchase_bill_number', 'exportable' => false, 'title' => 'Bill Number'],
            // __('purchase::app.menu.reference_no') => ['data' => 'reference_no', 'name' => 'reference_no', 'exportable' => false,'title' =>'Invoice Number'],
            // __('purchase::app.menu.po_type')    => ['data' => 'order_type', 'name' => 'order_type', 'exportable' => false],
            __('purchase::app.menu.project')    => ['data' => 'project_name', 'name' => 'project_name', 'title' => __('purchase::app.menu.project')],
            // __('purchase::app.menu.subproject') => ['data' => 'subproject', 'name' => 'subproject', 'title' => __('purchase::app.menu.subproject')],
            __('purchase::app.menu.billDate')   => ['data' => 'bill_date', 'name' => 'bill_date', 'exportable' => false],
            __('app.total')                     => ['data' => 'total', 'name' => 'total', 'exportable' => false],
            __('app.pending_status')            => ['data' => 'pending_status', 'name' => 'pending_status', 'title' => __('app.pending_status')],
            'MyTurn'                            => ['data' => 'is_my_turn', 'name' => 'is_my_turn', 'visible' => false],
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

        // Add status column if user has priority role
        $sessionUserId = Auth::id();
        $checkUser     = DB::table('priority_role')->where('user_id', $sessionUserId)->first();

        if ($checkUser) {
            $columns[__('app.status')] = [
                'data'  => 'approve_statuss',
                'name'  => 'approve_statuss',
                'title' => __('app.status'),
            ];
        }

        // Add vendor-specific column
        if (in_array('vendor', user_roles())) {
            $columns['draft'] = Column::computed('draft', __('app.submitBill'))
                ->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-center');
        } else {
            // Only non-vendors should see debit_details
            $columns['debit_details'] = Column::computed('debit_details', __('app.debit_details'))
                ->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-center');
        }

        // Add remaining computed columns
        // $columns['debit_details'] = Column::computed('debit_details', __('app.debit_details'))
        //     ->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-center');

        $columns['chat'] = Column::computed('chat', __('app.chat'))
            ->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-center');

        $columns['action'] = Column::computed('action', __('app.action'))
            ->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-right');

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
