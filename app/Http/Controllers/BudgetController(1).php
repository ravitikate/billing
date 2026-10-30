<?php
namespace App\Http\Controllers;

use App\DataTables\BudgetDataTable;
use App\DataTables\BudgetVsActualDataTable;

use App\Helper\Reply;
use App\Http\Requests\StoreBudget;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Currency;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\Tax;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Http\Controllers\PurchaseOrderController;


class BudgetController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->pageTitle = 'app.menu.budget';
        $this->pageTitle = 'Budget';
        $this->pageIcon  = 'ti-file';
        $this->middleware(function ($request, $next) {
            // abort_403(! in_array('budget', $this->user->modules));
            return $next($request);
        });
    }

    public function index(BudgetVsActualDataTable $dataTable)  {

        return $dataTable->render('budgets.index', $this->data);
    }

    public function create()  {

        $this->pageTitle      = __('modules.budget.createBudget');
        $this->projects       = Project::all();
        $this->currencies     = Currency::all();
        $this->invoiceSetting = invoice_setting();
        $this->zero           = '';
        $this->lastBudget     = (Budget::orderBy('id', 'desc')->first()->id ?? 0) + 1;
        $this->budget_prefix  = 'BGT';

        if (strlen($this->lastBudget) < 3) {
            $condition = 3 - strlen($this->lastBudget);
            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $this->taxes      = Tax::all();
        $this->categories = ProductCategory::all();

        // $this->view = 'budgets.ajax.create';
                $this->view = 'projects.ajax.budgetHead';


        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('budgets.create', $this->data);

    }

    public function addItem(Request $request)  {
        $this->product_category = DB::table('product_category')->where('id', $request->id)->first();
        return Reply::dataOnly(['status' => 'success', 'data' => $this->product_category]);
    }

    public function checkProject(Request $request)  {
        $q = Budget::where('project_id', $request->project_id)->first();
        if ($q) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Already Exist',
            ]);
        }
    }

    public function store(StoreBudget $request)   {
        if (! isset($request->item_name) || count(array_filter($request->item_name)) === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => __('At least one Budget Head  must be added.'),
            ], 422);
        }
        if (! isset($request->cost_per_item) || count(array_filter($request->cost_per_item, fn($cost) => $cost > 0)) === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Each item must have a cost greater than 0.'),
            ], 422);
        }

        $formattedDate = Carbon::createFromFormat('D d M Y', $request->valid_till)->format('Y-m-d');

        $budget = Budget::create([
            'budget_number'      => 'BGT#' . str_pad($request->budget_number ?? Budget::max('id') + 1, 3, '0', STR_PAD_LEFT),
            'valid_till'         => $formattedDate,
            'currency_id'        => $request->currency_id,
            'project_id'         => $request->project_id,
            'sub_total'          => $request->sub_total,
            'total'              => $request->total,
            'discount_value'     => $request->discount_value,
            'discount_type'      => $request->discount_type,
            'budget_description' => $request->budget_description,
        ]);

        foreach ($request->item_name as $index => $item) {
            if ($request->quantity[$index] > 0) {
                $imagePath = $request->hasFile("invoice_item_image.$index")
                ? $request->file("invoice_item_image.$index")->store('budgets', 'public')
                : null;

                BudgetItem::create([
                    'budget_id'              => $budget->id,
                    'item_name'              => $item,
                    'wbs'                    => $request->wbs[$index],
                    'tax'                    => json_encode((array) ($request->taxes[0][$index] ?? [])),
                    'quantity'               => $request->quantity[$index],
                    'product_category_id'    => $request->product_category_id[$index],
                    'cost_per_item'          => $request->cost_per_item[$index],
                    'amount'                 => $request->amount[$index],
                    'description'            => $request->description[$index] ?? null,
                    'invoice_item_image_url' => $imagePath,
                ]);
            }
        }

        return response()->json([
            'status'      => 'success',
            'message'     => __('messages.recordSaved'),
            'redirectUrl' => route('budget.index'),
        ]);

    }

    public function show($id)  {
        print_r($id);
    }

    public function edit($id) {
        $this->budget = Budget::with('items')->findOrFail($id);

        $this->pageTitle = $this->budget->budget_number;

        $this->currencies = Currency::all();
        $this->projects   = Project::all();
        $this->categories = ProductCategory::all();
        $this->taxes      = Tax::all();

        $this->view = 'budgets.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('budgets.create', $this->data);
    }

    public function update(Request $request, $id) {
        \DB::beginTransaction();

        try {
            // Find or create budget
            $budget = $id ? Budget::findOrFail($id) : new Budget();

            // Update budget fields
            $budget->fill([
                'budget_number'      => $request->budget_number,
                'valid_till'         => date('Y-m-d', strtotime($request->valid_till)),
                'currency_id'        => $request->currency_id,
                'project_id'         => $request->project_id,
                'budget_description' => $request->budget_description,
                'sub_total'          => $request->sub_total,
                'discount_value'     => $request->discount_value,
                'discount_type'      => $request->discount_type,
                'total'              => $request->total,
            ]);

            $budget->save();

            foreach ($request->item_name as $index => $item) {
                if ($request->quantity[$index] > 0) {
                    $imagePath = $request->invoice_item_image_url[$index] ?? null;

                    if ($request->hasFile("invoice_item_image") && isset($request->file("invoice_item_image")[$index])) {
                        $imagePath = $request->file("invoice_item_image")[$index]->store('budgets', 'public');
                    }

                    if (! empty($request->budget_items_id[$index])) {
                        $budgetItem = BudgetItem::find($request->budget_items_id[$index]);

                        if ($budgetItem) {
                            $budgetItem->update([
                                'item_name'              => $item,
                                'tax'                    => json_encode((array) ($request->taxes[0][$index] ?? [])),
                                'quantity'               => $request->quantity[$index],
                                'product_category_id'    => $request->product_category_id[$index],
                                'cost_per_item'          => $request->cost_per_item[$index],
                                'amount'                 => $request->amount[$index],
                                'description'            => $request->item_summary[$index] ?? null,
                                'invoice_item_image_url' => $imagePath ?? $budgetItem->invoice_item_image_url, // Retain old image if no new image is uploaded
                            ]);
                        }
                    } else {
                        BudgetItem::create([
                            'budget_id'              => $budget->id,
                            'item_name'              => $item,
                            'tax'                    => json_encode((array) ($request->taxes[0][$index] ?? [])),
                            'quantity'               => $request->quantity[$index],
                            'product_category_id'    => $request->product_category_id[$index],
                            'cost_per_item'          => $request->cost_per_item[$index],
                            'amount'                 => $request->amount[$index],
                            'description'            => $request->item_summary[$index] ?? null,
                            'invoice_item_image_url' => $imagePath,
                        ]);
                    }
                }
            }

            \DB::commit();
            return response()->json([
                'status'      => 'success',
                'message'     => __('messages.recordSaved'),
                'redirectUrl' => route('budget.index'),
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function budgetHeads(BudgetVsActualDataTable $dataTable) {
        $this->pageTitle = __('app.budgetHeadDetails');
        $this->Projects  = Project::all();
        return $dataTable->render('budgets.createbudgetHeads', $this->data);
    }

    public function getSubProjects(Request $request)
    {
        $data['subProjects'] = DB::table('tasks')
            ->where('project_id', $request->project_id)
            ->get();

        $data['groupCompany'] = DB::table('client_details')
                    ->select('company_name', 'id', 'user_id')
                    ->get();

        $data['getClient'] = DB::table('projects as p')
            ->join('client_details as c', 'c.user_id', '=', 'p.client_id')
            ->select('c.company_name', 'c.id', 'c.user_id')
            ->where('p.id', $request->project_id)
            ->first();
        if ($data['getClient']) {
            $comId = $data['getClient']->id;
            $data['getbilling'] = DB::table('billing_company')
                ->select('billing_company_name', 'id')
                ->where('company_group_id', $comId)
                ->get();
        } else {
            $data['getbilling'] = [];
        }
        return response()->json($data);
    }


    public function budgetHeaddetails($id)  {
        $this->budget_head_id = $id;
        $this->projectDetails = DB::table('budget_head as bh')
            ->join('projects as p', 'p.id', '=', 'bh.project_id')
            ->join('tasks as t', 't.id', '=', 'bh.sub_project_id')
            ->select(
                'bh.id','bh.status','bh.validate',
                'p.project_name',
                DB::raw("CONCAT(t.task_short_code, ' - ', t.heading) AS task_info"),
            )->where('bh.id', $this->budget_head_id)
            ->first();
        $this->pageTitle = $this->projectDetails->task_info . ' - ' . 'Budget Head Details';
     $budget_head_id = $id;

     $budgetHeadDetails = DB::table('budget_head_details as bhd')
                ->join('budget_head_name as bhn', 'bhn.id', '=', 'bhd.budget_head')
                ->join('budget_head as bh', 'bh.id', '=', 'bhd.budget_head_id')
                ->where('bhd.budget_head_id', $budget_head_id)
                ->select('bhd.wbs','bhd.action', 'bhd.val','bhd.status', 'bhd.budget_cost', 'bhn.name', 'bhn.type','bh.status as vStatus','bh.validate as Validate')
                ->get();

        // Group by WBS
        $grouped = $budgetHeadDetails->groupBy('wbs');

       $mergedBudget = $grouped->map(function ($items, $wbs) {
            $old = $items->first(function ($item) {
                return ($item->val == 2 && $item->status == 1  && $item->action == 1) || ($item->val == 1 && $item->status == 2 && $item->action == 1)|| ($item->val == 2 && $item->status == 2 && $item->action == 1);
            });

            $new = $items->first(function ($item) {
                return $item->val == 1 && $item->status == 1 && $item->action == 3;
            });

            $suffix = null;
            if (str_contains($wbs, '-')) {
                $parts = explode('-', $wbs);
                $lastPart = end($parts);
                $suffix = (int) $lastPart;
            }

            return [
                'wbs'         => $wbs,
                'prefix'      => $suffix,
                'name'        => $old->name ?? $new->name ?? null,
                'type'        => $old->type ?? $new->type ?? null,
                'old_val'     => $old ? (float) $old->budget_cost : 0,
                'new_val'     => $new ? (float) $new->budget_cost : 0,
            ];
        })->values();



        $this->budget_details = $mergedBudget;
        $this->approvalPermission = user()->permission('budget_approval');

         $this->budgetId =$id;

        return view('budgets.createbudgetHeaddetails', $this->data);
    }

    public function updateBudgetStatus(Request $request)  {
        $budgetId = $request->input('budgetId');
        $action   = $request->input('action');
        $comment  = $request->input('comment');
        $updateData = [
            'updated_at'  => now(),
        ];
        if ($action === 'approve') {
            $updateData['validate'] = 1;
            $updateData['action'] = 1;
            DB::table('budget_head')->where('id', $budgetId)->update($updateData);
            DB::table('budget_head_name')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->delete();

            DB::table('budget_head_details')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->delete();

            DB::table('budget_head_details')
                ->where('budget_head_id', $budgetId)
                ->where('val', 1)
                ->update(['status' => 2,'action' => 1]);
        } else {
            $updateData['validate'] = 2;
            $updateData['status']   = 2;

            DB::table('budget_head')->where('id', $budgetId)->update($updateData);

            DB::table('budget_head_name')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->where('status', 1)
                ->update(['val' => 1]);

            DB::table('budget_head_details')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->where('status', 1)
                ->update(['val' => 1,'action' => 2]);


            DB::table('budget_head_name')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->update(['status' => 2]);

            DB::table('budget_head_details')
                ->where('budget_head_id', $budgetId)
                ->where('val', 2)
                ->update(['status' => 2]);

        }
        $budgetData = [
            'budget_id'   => $budgetId,
            'action'      => $action,
            'comment'     => $comment,
            'user_id'     => auth()->id(),
            'updated_at'  => now(),
        ];
        DB::table('budget_update_activity')->insert($budgetData);
        return response()->json([
            'status' => 'success',
            'message' => 'Budget status updated successfully.',
            'redirect_url' => route('budget.index') // Replace with your actual redirect route
        ]);
    }

    public function getRejectionComment($id) {
    $latestComment = DB::table('budget_update_activity')
        ->where('budget_id', $id)
        ->orderByDesc('id')
        ->value('comment');

    return response()->json([
        'comment' => $latestComment
    ]);
}

   public function actualvereal($id) {
    $this->budget_head_id = $id;

    // Fetch project details
    $this->projectDetails = DB::table('budget_head as bh')
        ->join('projects as p', 'p.id', '=', 'bh.project_id')
        ->join('tasks as t', 't.id', '=', 'bh.sub_project_id')
        ->select(
            'bh.id',
            'bh.sub_project_id',
            'bh.project_id',
            'p.project_name',
            DB::raw("CONCAT(t.task_short_code, ' - ', t.heading) AS task_info")
        )
        ->where('bh.id', $this->budget_head_id)
        ->first();

    if (!$this->projectDetails) {
        abort(404, 'Project details not found');
    }

    $this->pageTitle = $this->projectDetails->task_info . ' - Budget Vs Actual';

    // Fetch budget details
    $budgetDetails = DB::table('budget_head_details as bhd')
        ->join('budget_head as bh', 'bh.id', '=', 'bhd.budget_head_id')
        ->join('budget_head_name as bhn', 'bhn.id', '=', 'bhd.budget_head')
        ->where('bh.id', $id)
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->where('bh.status', 2)
                    ->where('bh.validate', 2)
                    ->where('bhd.status', 2)
                    ->where('bhd.val', 2);
            })->orWhere(function ($q) {
                $q->where('bh.status', 1)
                    ->where('bh.validate', 2)
                    ->where('bhd.status', 2)
                    ->where('bhd.val', 2);
            })->orWhere(function ($q) {
                $q->where('bh.status', 1)
                    ->where('bh.validate', 1)
                    ->where('bhd.val', 1);
            });
        })
        ->select('bhd.wbs', 'bhd.budget_cost', 'bhn.wbs as head_wbs', 'bhn.name', 'bhn.type')
        ->get();

    // Fetch PO committed data
    $poAmount = DB::table('purchase_items as pi')
        ->select(
            DB::raw('TRIM(SUBSTRING_INDEX(pi.wbs, ".", 1)) as wbs'),
            DB::raw('SUM(pi.amount) as total_amount'),
            DB::raw('SUM(pi.gst_amount) as total_gst_amount')
        )
        ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
        ->where('po.validate', 'true')
        ->where('po.status', 'true')
        ->where('pi.po_type', 'boq')
        ->where('po.type', 'boq')
        ->where('pi.budget_head_id', $id)
        ->groupBy(DB::raw('TRIM(SUBSTRING_INDEX(pi.wbs, ".", 1))'))
        ->get()
        ->keyBy('wbs');

//   echo '<pre>'; print_r($poAmount);exit;
    // Fetch actual billed data
    $billAmount = DB::table('purchase_bill_items as pbi')
        ->select(
            DB::raw('TRIM(SUBSTRING_INDEX(pbi.wbs, ".", 1)) as wbs'),
            DB::raw('SUM(pbi.amount) as total_amount'),
            DB::raw('SUM(pbi.tax_amount) as total_tax_amount')
        )
        ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
        ->where('pbi.budget_head_id', $id)
        ->where('pb.status', 'open')
        ->groupBy(DB::raw('TRIM(SUBSTRING_INDEX(pbi.wbs, ".", 1))'))
        ->get()
        ->keyBy('wbs');

    // Normalize PO and Bill WBS keys
    $normalizedPoAmount = collect();
    foreach ($poAmount as $key => $value) {
        $normalizedPoAmount->put(trim($key), $value);
    }

    $normalizedBillAmount = collect();
    foreach ($billAmount as $key => $value) {
        $normalizedBillAmount->put(trim($key), $value);
    }

    // Merge Budget + PO + Bill
    $mergedBudget = $budgetDetails->map(function ($item) use ($normalizedPoAmount, $normalizedBillAmount) {
        $wbs = trim($item->wbs); // Ensure no leading/trailing space

        $poMatch = $normalizedPoAmount->get($wbs);
        $actualMatch = $normalizedBillAmount->get($wbs);

        return [
            'wbs'                       => $wbs,
            'name'                      => $item->name,
            'type'                      => $item->type,
            'budget_cost'              => (float) $item->budget_cost,
            'total_amount_gst'         => $poMatch ? (float) $poMatch->total_gst_amount : 0,
            'total_amount_without_gst' => $poMatch ? (float) $poMatch->total_amount : 0,
            'total_amount'             => $poMatch ? (float) $poMatch->total_amount + (float) $poMatch->total_gst_amount : 0,
            'actual_amount'            => $actualMatch ? (float) $actualMatch->total_amount + (float) $actualMatch->total_tax_amount : 0,
        ];
    });

    // Totals
    $totals = [
        'total_budget_cost'    => $mergedBudget->sum('budget_cost'),
        'total_committed_cost' => $mergedBudget->sum('total_amount'),
        'total_actual_cost'    => $mergedBudget->sum('actual_amount'),
    ];

    // Grouping for graph
    $buildItems = $mergedBudget->filter(fn($item) => $item['type'] === 'build')->values();
    $devTechItems = $mergedBudget->filter(fn($item) => in_array($item['type'], ['dev', 'tech']))->values();

    // Graph data
    $this->data['graphTotalWBS']     = $this->totalWBSGraph($mergedBudget);
    $this->data['totals']            = $totals;
    $this->data['graphTotal']        = $this->totalGraph($totals);
    $this->data['graphBuild']        = $this->totalWBSGraph($buildItems);
    $this->data['graphDevTech']      = $this->totalWBSGraph($devTechItems);
    $this->data['graphBuildTotal']   = $this->totalGraph($this->calculateTotals($buildItems));
    $this->data['graphDevTechTotal'] = $this->totalGraph($this->calculateTotals($devTechItems));

    // Final merged + diff data
    $this->data['merged_data']       = $mergedBudget;
    $this->data['actualVScurrent']   = $this->actualVScurrent($mergedBudget);

    return view('budgets.createactualvereal', $this->data);
}

    public function calculateTotals($items)  {
        $budget = 0;
        $committed = 0;
        $actual = 0;

        foreach ($items as $item) {
            $budget += $item['budget_cost'];
            $committed += $item['total_amount'];
            $actual += $item['actual_amount'];
        }

        return [
            'total_budget_cost' => $budget,
            'total_committed_cost' => $committed,
            'total_actual_cost' => $actual,
        ];
    }

    public function totalWBSGraph($items) {
        $labels = collect();
        $budgetData = collect();
        $poData = collect();
        $actualData = collect();

        foreach ($items as $item) {
            $labels->push($item['name']);
            $budgetData->push(round(($item['budget_cost'] ?? 0) / 10000000, 2)); // Cr
            $poData->push(round((($item['total_amount'] ?? 0)) / 10000000, 2)); // Cr
            $actualData->push(round((($item['actual_amount'] ?? 0)) / 10000000, 2)); // Cr
        }

        return [
            'labels' => $labels,
            'values' => [$budgetData, $poData, $actualData],
            'colors' => ['#007bff', '#ffc107', '#28a745'],
            'name' => ['Budget', 'Committed (PO)', 'Actual'],
            'tooltipLabels' => $labels->map(function ($label, $i) use ($budgetData, $poData, $actualData) {
                return [
                    'budget' => 'Budget: ₹ ' . number_format($budgetData[$i], 2) . ' Cr',
                    'committed' => 'Committed: ₹ ' . number_format($poData[$i], 2) . ' Cr',
                    'actual' => 'Actual: ₹ ' . number_format($actualData[$i], 2) . ' Cr',
                ];
            }),
        ];
    }

    public function totalGraph($totals)  {
        return [
            'labels' => ['Budget', 'Committed (PO)', 'Actual'],
            'values' => [
                collect([round(($totals['total_budget_cost'] ?? 0) / 10000000, 2)]),
                collect([round(($totals['total_committed_cost'] ?? 0) / 10000000, 2)]),
                collect([round(($totals['total_actual_cost'] ?? 0) / 10000000, 2)]),
            ],
            'colors' => ['#007bff', '#ffc107', '#28a745'],
            'name' => ['Budget', 'Committed (PO)', 'Actual'],
            'tooltipLabels' => collect([
                'Budget: ₹ ' . number_format(($totals['total_budget_cost'] ?? 0) / 10000000, 2) . ' Cr',
                'Committed: ₹ ' . number_format(($totals['total_committed_cost'] ?? 0) / 10000000, 2) . ' Cr',
                'Actual: ₹ ' . number_format(($totals['total_actual_cost'] ?? 0) / 10000000, 2) . ' Cr',
            ]),
        ];
    }

    public function actualVScurrent($mergedBudget) {
        $labels = collect();
        $budgetData = collect();
        $poData = collect();
        $actualData = collect();

        foreach ($mergedBudget as $item) {
            $labels->push($item['name']);
            $budgetData->push(round(($item['budget_cost'] ?? 0) / 10000000, 2)); // Divide by 1 Crore
            $poData->push(round((($item['total_amount'] ?? 0)) / 10000000, 2));
            $actualData->push(round((($item['actual_amount'] ?? 0)) / 10000000, 2));
        }

        $data = [
            'labels' => $labels,
            'values' => [
                $budgetData, // Budget (in Cr)
                $poData,     // PO (in Cr)
                $actualData  // Actual (in Cr)
            ],
            'colors' => ['#007bff', '#ffc107', '#28a745'],
            'name' => ['Budget', 'PO', 'Actual'],
            'tooltipLabels' => $labels->map(function ($label, $i) use ($budgetData, $poData, $actualData) {
                return [
                    'budget' => 'Budget: ₹ ' . number_format($budgetData[$i], 2) . ' Cr',
                    'po'     => 'PO: ₹ ' . number_format($poData[$i], 2) . ' Cr',
                    'actual' => 'Actual: ₹ ' . number_format($actualData[$i], 2) . ' Cr',
                ];
            })
        ];

        return $data;
    }

    public function edit_budget($id) {

            $this->pageTitle      = __('app.editBudget');
            $this->projects       = Project::all();
            $this->currencies     = Currency::all();
            $this->invoiceSetting = invoice_setting();
            $this->zero           = '';
            $this->lastBudget     = (Budget::orderBy('id', 'desc')->first()->id ?? 0) + 1;
            $this->budget_prefix  = 'BGT';
            $this->budget_id = $id;

            if (strlen($this->lastBudget) < 3) {
                $condition = 3 - strlen($this->lastBudget);
                for ($i = 0; $i < $condition; $i++) {
                    $this->zero = '0' . $this->zero;
                }
            }

            $this->taxes      = Tax::all();
            $this->categories = ProductCategory::all();

            // $this->view = 'budgets.ajax.create';
                    $this->view = 'projects.ajax.edit_budget';


            if (request()->ajax()) {
                return $this->returnAjax($this->view);
            }

            return view('budgets.create', $this->data);

    }

    public function approvePage($id)  {
    $this->po_id = $id;

    // Fetch the approved purchase order
    $order = DB::table('purchase_orders')
        ->where('id', $id)
        // ->where('status', true)
        ->first();

    if (!$order) {
        abort(404, 'Purchase order not found or not approved.');
    }

    $this->budgetHeadId = $order->budgetHeadId;

    // Fetch project details
    $this->projectDetails = DB::table('budget_head as bh')
        ->join('projects as p', 'p.id', '=', 'bh.project_id')
        ->join('tasks as t', 't.id', '=', 'bh.sub_project_id')
        ->select(
            'bh.id',
            'bh.sub_project_id',
            'bh.project_id',
            'p.project_name',
            DB::raw("CONCAT(t.task_short_code, ' - ', t.heading) AS task_info")
        )
        ->where('bh.id', $this->budgetHeadId)
        ->first();

    if (!$this->projectDetails) {
        abort(404, 'Project details not found');
    }

    $this->pageTitle = $this->projectDetails->task_info . ' Purchase Order / Work Order Overview';

     $this->poValidation = DB::table('purchase_orders as pi')
                    ->where('pi.id', $id)
                    ->select('pi.validate', 'pi.status')
                    ->first();

    // Fetch budget details
    $budgetDetails = DB::table('budget_head_details as bhd')
            ->join('budget_head as bh', 'bh.id', '=', 'bhd.budget_head_id')
            ->join('budget_head_name as bhn', 'bhn.id', '=', 'bhd.budget_head')
            ->where('bh.id', $this->budgetHeadId)
            ->select('bhd.wbs', 'bhd.budget_cost', 'bhn.wbs as head_wbs', 'bhn.name', 'bhn.type')
            ->get();

    // Fetch committed (approved PO) data
    $poAmount = DB::table('purchase_items as pi')
                ->select(
                    DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1) as wbs'),
                    DB::raw('SUM(pi.amount) as total_amount'),
                    DB::raw('SUM(pi.gst_amount) as total_gst_amount')
                )
                ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
                ->where('po.validate', 'true')
                ->where('po.status', 'true')
                ->where('pi.po_type', 'boq')
                ->where('po.type', 'boq')
                ->where('pi.budget_head_id', $this->budgetHeadId)
                ->groupBy(DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1)'))
                ->get()
                ->keyBy('wbs');

    // Fetch current PO data (pending validation)
    $currentPo = DB::table('purchase_items as pi')
                ->select(
                    DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1) as wbs'),
                    DB::raw('SUM(pi.amount) as total_amount'),
                    DB::raw('SUM(pi.gst_amount) as total_gst_amount')
                )
                ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
                ->where('po.validate', 'false')
                ->where('po.status', 'true')
                ->where('pi.po_type', 'boq')
                ->where('po.type', 'boq')
                ->where('pi.purchase_order_id', $id)
                ->groupBy(DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1)'))
                ->get()
                ->keyBy('wbs');
//  echo '<pre>'; print_r($budgetDetails);
//              echo '<pre>'; print_r($poAmount);
//              echo '<pre>'; print_r($currentPo);  exit;
    // Merge data

            $normalizedPoAmount = collect();
            foreach ($poAmount as $key => $value) {
                $normalizedPoAmount->put(trim($key), $value);
            }

            $normalizedCurrentPo = collect();
            foreach ($currentPo as $key => $value) {
                $normalizedCurrentPo->put(trim($key), $value);
            }


           $mergedBudget = $budgetDetails->map(function ($item) use ($normalizedPoAmount, $normalizedCurrentPo) {
                $wbs = trim($item->wbs);

                $po = $normalizedPoAmount->get($wbs);
                $current = $normalizedCurrentPo->get($wbs);

                return [
                    'wbs' => $item->wbs,
                    'name' => $item->name,
                    'budget_cost' => $item->budget_cost,
                    'type' => $item->type,
                    'po_amount' => ($po->total_amount ?? 0) + ($po->total_gst_amount ?? 0),
                    'current_po_amount' => ($current->total_amount ?? 0) + ($current->total_gst_amount ?? 0),
                ];
            });



//  echo '<pre>'; print_r($mergedBudget);  exit;
    // Totals
    $totals = [
        'total_budget_cost'    => $mergedBudget->sum('budget_cost'),
        'total_committed_cost' => $mergedBudget->sum('total_amount') + $mergedBudget->sum('po_tax'),
    ];

    // Separate build and dev/tech items
    $buildItems = $mergedBudget->filter(fn ($item) => $item['type'] === 'build')->values();
    $devTechItems = $mergedBudget->filter(fn ($item) => in_array($item['type'], ['dev', 'tech']))->values();

    // Pass to view
    $this->data['totals'] = $totals;
    $this->data['merged_data'] = $mergedBudget;
    $this->data['build_items'] = $buildItems;
    $this->data['dev_tech_items'] = $devTechItems;

    return view('budgets.po_validation', $this->data);
}

    public function approveReject(Request $request) {
      $po_id = $request->input('po_id');


    $action = $request->input('action');

    $validate = ($action === 'approve') ? true : false;

    $order = DB::table('purchase_orders')->where('id', $po_id)->first();

        if (!$order) {
            return redirect()->back()->with('error', 'PO not found.');
        }

    // This will print "true" or "false" as string, not 1/blank
    $val = var_export($validate, true);

        $updatedAt = Carbon::now('Asia/Kolkata');
        DB::table('purchase_orders')
            ->where('id', $po_id)
            ->update([
                'validate' => $val,
                'status' => $val,
                'updated_at' => $updatedAt,
            ]);
        $updatedAt = Carbon::now('Asia/Kolkata');
       DB::table('po_wo_status')->insert([
                'user_id'    => auth()->id(),
                'po_wo_id'   => $po_id,
                'remark'     => $request->input('reject_comment'),
                'status'     => $val,
                'updated_at' => $updatedAt,
            ]);

         if ($order->order_type === 'purchase_order') {
            return redirect()->action([PurchaseOrderController::class, 'index'])
                ->with('success', "Purchase Order has been " . ($validate ? "approved" : "rejected") . " successfully.");
        } elseif ($order->order_type === 'work_order') {
            return redirect()->route('work_order.work-order')
                ->with('success', "Work Order has been " . ($validate ? "approved" : "rejected") . " successfully.");
        }

        // Fallback if order_type is unexpected
        return redirect()->back()->with('error', 'Unknown order type.');
}

    public function committed_breakup(Request $request) {
        $wbsCode = trim($request->wbs);
        $this->wbs = trim($request->wbs);
        $this->budgetId = trim($request->budgetId);
        $budgetId = trim($request->budgetId);

        // Extract only the part before the first dot for comparison
        $trimmedWbsCode = $wbsCode;

        $this->pageTitle = 'Breakup for Committed Amount of WBS: ' . $trimmedWbsCode;

        $this->poBreakups = DB::table('purchase_items as pi')
            ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
            ->join('purchase_vendors as pv', 'pv.id', '=', 'po.vendor_id')
            ->join('tasks as t', 't.id', '=', 'po.subproject_id')
            ->join('users as u', 'u.id', '=', 'pv.user_id')
            ->select(
                'po.po_number',
                'u.name',
                't.heading',
                DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1) as wbs'),
                'po.id',
                DB::raw('SUM(pi.amount) as total_amount'),
                DB::raw('SUM(pi.gst_amount) as total_gst_amount')
            )
            ->where(DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1)'), $trimmedWbsCode)
            ->where('pi.budget_head_id', $budgetId)
            ->groupBy(
                'po.po_number',
                'u.name',
                't.heading',
                'po.id',
                DB::raw('SUBSTRING_INDEX(pi.wbs, ".", 1)')
            )
            ->get()
            ->map(function ($item) {
                return (object) $item;
            });

        return view('budgets.committed_breakup', $this->data);
}

    public function committedBreakupOfPo(Request $request) {
        $po_id = trim($request->input('bill_id'));
        $po_number = trim($request->input('bill_number'));
        $wbs = trim($request->input('wbs'));
        $this->pageTitle = 'Breakup for Committed Amount of WBS: ' . $wbs . ' and PO/WO Number is ' . ($po_number ?? '-');
        $this->wbs = $wbs;
        $this->po_number = $po_number;

        $this->poBreakups = DB::table('purchase_items as pi')
                        ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
                        ->join('purchase_vendors as pv', 'pv.id', '=', 'po.vendor_id')
                        ->join('tasks as t', 't.id', '=', 'po.subproject_id')
                        ->join('users as u', 'u.id', '=', 'pv.user_id')
                        ->where('pi.purchase_order_id', $po_id)
                        ->where('pi.po_type', 'boq')
                        ->where('po.type', 'boq')
                        ->where('po.validate', 'true')
                        ->where('po.status', 'true')
                        ->whereRaw('TRIM(pi.wbs) LIKE ?', [$wbs . '%'])
                        ->select(
                            'po.po_number',
                            'po.id',
                            'pi.wbs',
                            'u.name',
                            't.heading',
                            'pi.item_name',
                            DB::raw('pi.amount as total_amount'),
                            DB::raw('pi.gst_amount as total_gst_amount')
                        )
                        ->get();



        // Return the view with data
        return view('budgets.committedBreakupOfPo', $this->data);

}

    public function actual_breakup(Request $request) {
        $wbsCode = trim($request->wbs);
        $this->wbs = trim($request->wbs);
        $this->budgetId = trim($request->budgetId);
        $budgetId = trim($request->budgetId);
        $this->pageTitle = 'Breakup for Actual Amount of WBS: ' . $wbsCode;

        $setting = DB::table('purchase_settings')->orderBy('id')->first();

        $this->actualBreakup = DB::table('purchase_bill_items as pb')
                    ->join('purchase_bills as pbb', 'pbb.id', '=', 'pb.purchase_bills_id')
                    ->join('purchase_orders as po', 'po.id', '=', 'pbb.purchase_order_id')
                    ->join('tasks as t', 't.id', '=', 'po.subproject_id')
                    ->join('purchase_vendors as pv', 'pv.id', '=', 'pbb.purchase_vendor_id')
                    ->join('users as u', 'u.id', '=', 'pv.user_id')
                    ->select([
                        DB::raw("CONCAT_WS('{$setting->bill_number_separator}', '{$setting->bill_prefix}', LPAD(pbb.purchase_bill_number, {$setting->bill_number_digit}, '0')) as formatted_bill_number"),
                        DB::raw('SUM(pb.amount) as total_amount'),
                        DB::raw('SUM(pb.tax_amount) as total_tax_amount'),
                        'u.name',
                        't.heading',
                        'pbb.id',
                        DB::raw('SUBSTRING_INDEX(pb.wbs, ".", 1) as wbs')
                    ])
                    ->where(DB::raw('SUBSTRING_INDEX(pb.wbs, ".", 1)'), $wbsCode)
                    ->where('pb.budget_head_id', $budgetId)
                    ->where('pb.quantity', '>', 0)
                    ->groupBy('formatted_bill_number', 'u.name', 't.heading', 'pbb.id', DB::raw('SUBSTRING_INDEX(pb.wbs, ".", 1)'))
                    ->get()
                    ->map(function ($item) {
                        return (object) $item;
                    });


        return view('budgets.actual_breakup', $this->data);

}

    public function actualBreakupOfPo(Request $request) {
        $po_id = trim($request->input('bill_id'));
        $po_number = trim($request->input('bill_number'));
        $wbs = trim($request->input('wbs'));


        $this->pageTitle = 'Breakup for PO/WO: ' . $po_number;

        $this->po_id = $po_id;
        $this->po_number = $po_number;
        // print_r($po_id);
        // print_r($po_number);
        // exit;
        // Compare the last part of pi.wbs with the provided $wbs value
        $this->poBreakups = DB::table('purchase_bill_items as pi')
                    ->join('purchase_bills as po', 'po.id', '=', 'pi.purchase_bills_id')
                    ->select('pi.wbs', 'pi.purchase_bills_id', 'pi.purchase_bills_id', 'pi.item_name', 'pi.amount', 'pi.tax_amount')
                    ->where('pi.purchase_bills_id', $po_id)
                    ->where('pi.quantity', '>', 0)
                    ->whereRaw('TRIM(pi.wbs) LIKE ?', [($wbs) . '%'])  // Apply TRIM and match wbs suffix
                    ->get();

        // Return the view with data
        return view('budgets.actualBreakupOfPo', $this->data);
}

    public function committedProjectBreakup(Request $request)  {
          $projectId = $request->input('project_id');
          $wbsSuffix = trim($request->input('wbs'));


            $projectName = DB::table('projects')
                             ->where('id', $projectId)
                             ->value('project_name');

            $budgetHead = DB::table('budget_head_name')
                            ->where(DB::raw("SUBSTRING_INDEX(wbs, '-', -1)"), $wbsSuffix)
                            ->first();
            $budgetHeadName = $budgetHead ? $budgetHead->name : '-';

            $this->pageTitle = 'Breakup for Committed Amount of WBS: ' . $budgetHeadName . ' and Project is ' . ($projectName ?? '-');



            $this->budgetHeadName = $budgetHeadName;
            $this->projectName = $projectName;


            $this->poBreakups = DB::table('purchase_items as pi')
                        ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
                        ->join('purchase_vendors as pv', 'pv.id', '=', 'po.vendor_id')
                        ->join('tasks as t', 't.id', '=', 'po.subproject_id')
                        ->join('users as u', 'u.id', '=', 'pv.user_id')
                        ->where('po.project_id', $projectId)
                        ->where('pi.po_type', 'boq')
                        ->where('po.type', 'boq')
                        ->where('po.validate', 'true')
                        ->where('po.status', 'true')
                        ->where(DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, '-', -1), '.', 1)"), '=', $wbsSuffix)
                        ->select(
                            'po.po_number',
                            'po.id',
                            DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, '-', -1), '.', 1) as wbs_suffix"),
                            'u.name',
                            't.heading',
                            DB::raw('SUM(pi.amount) as total_amount'),
                            DB::raw('SUM(pi.gst_amount) as total_gst_amount')
                        )
                        ->groupBy('po.po_number', 'po.id', 'wbs_suffix', 'u.name', 't.heading')
                        ->get();



        // Return the view with data
        return view('budgets.committedProjectBreakup', $this->data);

}

    public function committedProjectBreakupOfPo(Request $request)  {
        $po_id = $request->input('po_id');
        $po_number = $request->input('po_number');
        $wbs = $request->input('wbs'); // should be '4' in case of 'PL1378-Bldg-4.1.7'

        $this->pageTitle = 'Breakup for PO/WO: ' . $po_number;

        $this->po_id = $po_id;
        $this->po_number = $po_number;

        // Extract the number after last dash and before decimal and compare
        $this->poBreakups = DB::table('purchase_items as pi')
            ->join('purchase_orders as po', 'po.id', '=', 'pi.purchase_order_id')
            ->select('pi.wbs', 'pi.purchase_order_id', 'po.po_number', 'pi.item_name', 'pi.amount', 'pi.gst_amount')
            ->where('pi.purchase_order_id', $po_id)
            ->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, '-', -1), '.', 1) = ?", [$wbs])
            ->get();

        return view('budgets.committedProjectBreakupOfPo', $this->data);
}

    public function actualProjectBreakup(Request $request)   {
          $projectId = $request->input('project_id');
          $wbsSuffix = trim($request->input('wbs'));
        //   print_r($wbsSuffix);exit;
            $projectName = DB::table('projects')
                             ->where('id', $projectId)
                             ->value('project_name');

            $budgetHead = DB::table('budget_head_name')
                            ->where(DB::raw("SUBSTRING_INDEX(wbs, '-', -1)"), $wbsSuffix)
                            ->first();
            $budgetHeadName = $budgetHead ? $budgetHead->name : '-';

            $this->pageTitle = 'Breakup for Committed Amount of WBS: ' . $budgetHeadName . ' and Project is ' . ($projectName ?? '-');
            $this->budgetHeadName = $budgetHeadName;
            $this->projectName = $projectName;
            $setting = DB::table('purchase_settings')->orderBy('id')->first();
            $this->poBreakups = DB::table('purchase_bills as pb')
                        ->join('purchase_bill_items as pbi', 'pbi.purchase_bills_id', '=', 'pb.id')
                        ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
                        ->join('tasks as t', 't.id', '=', 'po.subproject_id')
                        ->join('purchase_vendors as pv', 'pv.id', '=', 'pb.purchase_vendor_id')
                        ->join('users as u', 'u.id', '=', 'pv.user_id')
                        ->select(
                            DB::raw("CONCAT_WS('{$setting->bill_number_separator}', '{$setting->bill_prefix}', LPAD(pb.purchase_bill_number, {$setting->bill_number_digit}, '0')) as formatted_bill_number"),
                            't.heading', 'u.name', 'pbi.wbs', 'pb.id', 'pbi.item_name',
                            DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(pbi.wbs, '-', -1), '.', 1) AS wbs_suffix"),
                            DB::raw('SUM(pbi.amount) AS total_amount'),
                            DB::raw('SUM(pbi.tax_amount) AS total_tax_amount')
                        )
                        ->where('po.project_id', $projectId)
                        ->where('pbi.quantity', '>', 0)
                        ->where(DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(pbi.wbs, '-', -1), '.', 1)"), '=', $wbsSuffix)
                        ->groupBy(
                            DB::raw("CONCAT_WS('{$setting->bill_number_separator}', '{$setting->bill_prefix}', LPAD(pb.purchase_bill_number, {$setting->bill_number_digit}, '0'))"),
                              )
                        ->get();
        return view('budgets.actualProjectBreakup', $this->data);

}

    public function actualProjectBreakupOfPo(Request $request) {
        $po_id = $request->input('bill_id');
        $po_number = $request->input('bill_number');
        $wbs = $request->input('wbs');

        $this->pageTitle = 'Breakup for PO/WO: ' . $po_number;

        $this->po_id = $po_id;
        $this->po_number = $po_number;

        // Compare the last part of pi.wbs with the provided $wbs value
        $this->poBreakups = DB::table('purchase_bill_items as pi')
            ->join('purchase_bills as po', 'po.id', '=', 'pi.purchase_bills_id')
            ->select('pi.wbs', 'pi.purchase_bills_id', 'pi.purchase_bills_id', 'pi.item_name', 'pi.amount', 'pi.tax_amount')
            ->where('pi.purchase_bills_id', $po_id)
            ->where('pi.quantity', '>', 0)
            ->whereRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(pi.wbs, '-', -1), '.', 1) = ?", [$wbs]) // Compare wbs suffix
            ->get();

        // Return the view with data
        return view('budgets.actualProjectBreakupOfPo', $this->data);
}

}

