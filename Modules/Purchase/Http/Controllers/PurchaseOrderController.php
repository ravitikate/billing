<?php

namespace Modules\Purchase\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tax;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Product;
use App\Models\Currency;
use App\Models\UnitType;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Modules\Purchase\Entities\PurchaseItem;
use Illuminate\Contracts\Support\Renderable;
use Modules\Purchase\Entities\PurchaseOrder;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Entities\PurchaseItemTax;
use Modules\Purchase\Entities\PurchaseSetting;
use App\Http\Controllers\AccountBaseController;
use Modules\Purchase\Entities\PurchaseItemImage;
use Modules\Purchase\Events\NewPurchaseOrderEvent;
use Modules\Purchase\Entities\PurchaseOrderHistory;
use Modules\Purchase\Entities\PurchasePaymentHistory;
use Modules\Purchase\DataTables\PurchaseOrderDataTable;
use Modules\Purchase\DataTables\WorkOrderDataTable;
use Modules\Purchase\Entities\CompanyGroup;
use Modules\Purchase\Entities\BillingCompany;
use App\Models\BudgetHeadName;

use Modules\Purchase\Http\Requests\PurchaseOrder\StoreRequest;
use Modules\Purchase\Http\Requests\PurchaseOrder\UpdateRequest;
use App\Models\Project;
use App\Models\Task;

use App\Traits\ImportExcel;
use App\Imports\POOrderImport;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends AccountBaseController
{
    use ImportExcel;
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'purchase::app.menu.purchaseOrder';
        $this->purchaseSetting = PurchaseSetting::first();
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(PurchaseOrderDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_purchase_order');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'purchase::app.menu.purchaseOrder';

        return $dataTable->render('purchase::purchase-order.index', $this->data);
    }



    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->pageTitle = __('purchase::app.menu.purchaseOrder');
        $this->lastOrder = PurchaseOrder::lastOrderNumber() + 1;
        $this->products = Product::where('status', 'active')->get();
        $this->categories = ProductCategory::all();
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        $this->vendors = PurchaseVendor::all();
        $this->addresses = CompanyAddress::all();
        $this->currencies = Currency::all();
        $this->projects = Project::all();
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->vendorID = request()->purchase_order_vendor_id;
        $this->purchaseVendorID = PurchaseVendor::where('id', $this->vendorID)->first();
        $this->company = DB::table('client_details')
                    ->select('company_name', 'id')
                    ->get();
        // print_r($this->company);exit;
        $this->zero = '';

        if (strlen($this->lastOrder) < $this->purchaseSetting->purchase_order_number_digit) {
            $condition = $this->purchaseSetting->purchase_order_number_digit - strlen($this->lastOrder);

            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $this->linkOrderPermission = user()->permission('link_order_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;

        if (request()->ajax()) {
            $html = view('purchase::purchase-order.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::purchase-order.ajax.create';

        return view('purchase::purchase-order.create', $this->data);

    }


  public function store(StoreRequest $request)
    {
        $data = $request->all();

         $pro = $request->project_id;
         $sub = $request->subproject_id;

         $project = DB::table('projects')
            ->where('id', $pro)
            ->select('project_short_code', 'client_id')
            ->first();

        $projectShortCode = $project->project_short_code ?? null;
        $clientId = $project->client_id ?? null;
        $clientUniqueId = DB::table('client_details')
            ->where('user_id', $clientId)
            ->value('client_unique_id');
        $budgetHeadId = DB::table('budget_head')
            ->where('project_id', $pro)
            ->where('sub_project_id', $sub)
            ->value('id');

        $order = new PurchaseOrder();
        $order->purchase_order_number = $request->purchase_order_number;
        $order->currency_id = $request->currency_id ?? null;
        $order->default_currency_id = company()->currency_id;
        $order->exchange_rate = $request->exchange_rate;
        $order->vendor_id = $request->vendor_id ?? null;
        $order->project_id = $request->project_id;
         $order->categorytype = $request->categorytype;
        $order->subproject_id = $request->subproject_id;
        $order->po_number =$request->po_number;
        $order->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->purchase_date)->format('Y-m-d');
        $order->expected_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');
        $order->sub_total = 0;
        $order->discount = 0;
        $order->calculate_tax = 'after_discount';
        $order->discount_type = 'percent';
        $order->order_type = $request->order_type;
        $order->company_group_id = $request->company_group_id;
        $order->billing_company_id = $request->billing_company_id;
        $order->reference_number = $request->reference_number;
        $order->category = $request->category;
        $order->po_number_type = $request->type;
        $order->delivery_status = 'in_transaction';
        $order->budgetHeadId =$budgetHeadId;
        $order->address_id = 1;

        $order->minimum_billing_limit = $request->minbilllimit ?? null;
        $order->billing_cycle = $request->billingcycle ?? null;
        $order->billing_duration = $request->billingDuration ?? null;
        $order->reserved_amt_in_percent = $request->reserveAmt ?? null;
        $order->save();

        $this->purchase_order_id = $order->id;
        $userId = auth()->id();


        DB::table('po_wo_approve_reject_history')->insert([
            'user_id'  => $userId,
            'po_wo_id' => $order->id,
            'reason'   => 'Work Order Submitted for Approval'
        ]);
        try {
            $this->importFileProcessPo($request, POOrderImport::class, $order->id);
            $view = view('projects.ajax.po_import_progress', array_merge($this->data, [
                'purchase_order_id' => $order->id,
                'orderId' => $order->id,
                'order_type' => $request->order_type  // Pass order_type here as well
            ]))->render();
            return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
        } catch (\Exception $e) {
            return Reply::error($e->getMessage());
        }
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->order = PurchaseOrder::with('vendor', 'files', 'currency', 'items', 'items.unit', 'items.purchaseItemImage')->findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_order');
        $this->deletePermission = user()->permission('delete_purchase_order');
        $this->addInvoicesPermission = user()->permission('add_purchase_order');
        $this->approvePermission = user()->permission('po_wo_approve');
        // print_r($this->approvePermission);exit;
        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->order->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $this->order->send_status)
            || ($this->viewPermission == 'both' && ($this->order->added_by == user()->id))
        ));

        $this->pageTitle = $this->order->purchase_order_number;

        $this->discount = 0;

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);
            }
            else {
                $this->discount = $this->order->discount;
            }
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[ $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[ $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[  $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[ $this->tax->rate_percent . '%'] = $taxList[  $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[ $this->tax->rate_percent . '%'] = $taxList[  $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }
        $this->taxes = $taxList;
            // dd($this->taxes);
        $tab = request('tab');
        $this->po_id = $id;
         $this->commentsHistory = DB::table('po_wo_approve_reject_history as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->select('p.*', 'u.name as user_name')
            ->where('p.po_wo_id', $id)
            ->orderBy('p.created_at', 'desc')
            ->get();
        switch ($tab) {
        case 'files':
            $this->view = 'purchase::purchase-order.ajax.files';
            break;
        case 'history':
             $this->view = 'purchase::purchase-order.ajax.history';
                break;
        default:
            $this->view = 'purchase::purchase-order.ajax.overview';
            break;
        }

        $this->settings = company();

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'overview';
        return view('purchase::purchase-order.show', $this->data);

    }

        public function show_stages_id($id)
    {

        $this->order = PurchaseOrder::with('vendor', 'files', 'currency', 'stages', 'stages.unit')
                ->where('type', 'stages')
                ->findOrFail($id);

        $this->pageTitle = $this->order->purchase_order_number;
        $this->discount = 0;
        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);
            }
            else {
                $this->discount = $this->order->discount;
            }
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->where('po_type', 'stages')
            ->get();

        $this->order->items = $this->order->stages;
        $this->po_id = $id;

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $tab = request('tab');

        switch ($tab) {
        case 'files':
            $this->view = 'purchase::purchase-order.ajax.files';
            break;
        case 'history':
            return $this->history($id);
        default:
            $this->view = 'purchase::purchase-order.ajax.overview';
            break;
        }
        $this->settings = company();
        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'overview';
        return view('purchase::purchase-order.show_stages', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->order = PurchaseOrder::with(['items', 'vendor', 'items.itemTaxes','company_group','billing_company'
        ])->findOrFail($id);

        $this->editPermission = user()->permission('edit_purchase_order');

        abort_403(!(
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->order->added_by == user()->id)
        ));

        $this->pageTitle = $this->order->purchase_order_number;

        $this->vendors = PurchaseVendor::all();
        $this->vendorID = request()->purchase_order_vendor_id;
        $this->purchaseVendorID = PurchaseVendor::where('id', $this->vendorID)->first();
        $this->currencies = Currency::all();
        $this->units = UnitType::all();
        $this->addresses = CompanyAddress::all();

        $this->taxes = Tax::all();
        $this->products = Product::where('status', 'active')->get();
        $this->categories = ProductCategory::all();
        $this->linkOrderPermission = user()->permission('link_order_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');
        $this->company = DB::table('company_group')
                    ->where('status', 1)
                    ->select('company_name', 'id')
                    ->get();

        $this->billingCompanies = BillingCompany::where('company_group_id', $this->order->company_group_id)
                                            ->get();
        $this->projects = Project::all();

        $this->subProject = Task::where('id', $this->order->subproject_id)->get();



        // dd($this->subProject);
        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->order->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->purchaseSetting = PurchaseSetting::first();


        $this->companyAddresses = CompanyAddress::all();

        // dd($this->order);

        if (request()->ajax()) {
            $html = view('purchase::purchase-order.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        $this->po_wo_id=$id;

        $this->view = 'purchase::purchase-order.ajax.editPo';

        return view('purchase::purchase-order.create', $this->data);

    }



    public function edit_po($id)
    {

        $this->order = PurchaseOrder::with('vendor', 'files', 'currency', 'items', 'items.unit', 'items.purchaseItemImage')->findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_order');
        $this->deletePermission = user()->permission('delete_purchase_order');
        $this->addInvoicesPermission = user()->permission('add_purchase_order');
        $this->approvePermission = user()->permission('po_wo_approve');
        $this->units = UnitType::all();
        // print_r($this->approvePermission);exit;
        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->order->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $this->order->send_status)
            || ($this->viewPermission == 'both' && ($this->order->added_by == user()->id))
        ));

        $this->pageTitle = $this->order->purchase_order_number;

        $this->discount = 0;

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);
            }
            else {
                $this->discount = $this->order->discount;
            }
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();


        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[ $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[ $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[  $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[ $this->tax->rate_percent . '%'] = $taxList[  $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[ $this->tax->rate_percent . '%'] = $taxList[  $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }
        $this->taxes = $taxList;
            // dd($this->taxes);
        $tab = request('tab');
        $this->po_id = $id;
        $budgetHeadId = $this->order->budgetHeadId;

        $this->wbsList = DB::table('budget_head_name')
                        ->where('budget_head_id', $budgetHeadId)
                        ->pluck('wbs');   // returns collection of all WBS





        switch ($tab) {
        default:
            $this->view = 'purchase::purchase-order.ajax.edit_po_order';
            break;
        }

        $this->settings = company();

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'overview';
        return view('purchase::purchase-order.show', $this->data);

    }


 public function updatePo(Request $request, $id)
{
    // dd($request->all());
    $order = PurchaseOrder::findOrFail($id);

    // Get budget_head_id from PurchaseItem using purchase_order_id
    $purchaseItem = PurchaseItem::where('purchase_order_id', $id)->first();
    $budgetHeadId = $purchaseItem ? $purchaseItem->budget_head_id : null;


    $companyId = company()->id;

    DB::beginTransaction();

    try {
        // Get existing item IDs to track deleted ones
        $existingItemIds = $order->items()->where('type', 'item')->pluck('id')->toArray();
        $updatedItemIds = [];

        // Update or create items
        if ($request->has('items')) {
            foreach ($request->items as $itemData) {
                if (!empty($itemData['item_name'])) {
                    $quantity = floatval($itemData['quantity'] ?? 0);
                    $unitPrice = floatval($itemData['unit_price'] ?? 0);
                    $retentionPercent = floatval($itemData['retention_percent'] ?? 0);
                    $taxPercent = floatval($itemData['tax_percent'] ?? 0);

                    $baseAmount = $quantity * $unitPrice;
                    $retention = $retentionPercent;
                    $retentionAmount = ($baseAmount * $retentionPercent) / 100;
                    $amountAfterRetention = $baseAmount - $retentionAmount;
                    $gstAmount = ($amountAfterRetention * $taxPercent) / 100;
                    $wbsWithPrefix = '';
                    $finalBudgetHeadId = '';
                    $budgetHead = null;

                    if (!empty($itemData['wbs']) || !empty($itemData['prefix'])) {
                        if (!empty($itemData['wbs']) && !empty($itemData['prefix'])) {
                            $wbsWithPrefix = $itemData['wbs'] . '.' . $itemData['prefix'];
                        } elseif (!empty($itemData['wbs'])) {
                            $wbsWithPrefix = $itemData['wbs'];
                        } elseif (!empty($itemData['prefix'])) {
                            $wbsWithPrefix = $itemData['prefix'];
                        }

                        if (!empty($wbsWithPrefix)) {
                            $budgetHead = BudgetHeadName::where('wbs', $itemData['wbs'])->first();
                            $finalBudgetHeadId = $budgetHead->id;
                        }
                    }

                     $gstid = Tax::firstOrCreate(
                            ['rate_percent' => $taxPercent]
                        );
                    if (!$gstid && $taxPercent > 0) {
                        $gstid = new Tax();
                        $gstid->company_id = $companyId;
                        $gstid->name = 'GST ' . $taxPercent . '%';
                        $gstid->rate_percent = $taxPercent;
                        $gstid->status = 'active';
                        $gstid->save();
                    }
                        $unitType = null;
                        if (!empty($itemData['unit_id'])) {
                            $unitType = UnitType::find($itemData['unit_id']);
                        }
                    if (isset($itemData['id']) && !empty($itemData['id'])) {
                        $item = PurchaseItem::find($itemData['id']);
                        if ($item) {
                            $item->update([
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'unit_id' => $itemData['unit_id'] ?? null,
                                'retention' =>$retentionAmount,
                                'retention_amount' => $retention,
                                'taxes_percent' => $taxPercent,
                                'gst_amount' => $gstAmount,
                                'amount' => $amountAfterRetention,
                            ]);
                            $updatedItemIds[] = $item->id;
                        }
                    } else {
                        $product = new Product();
                        $product->company_id = $companyId;
                        $product->department_id = 1;
                        $product->name = $itemData['item_name'];
                        $product->price = $unitPrice;
                        $product->purchase_price = $unitPrice;
                        $product->purchase_information = 1;
                        $product->taxes = implode(',', ["$gstid->id"]);
                        $product->category_id = 1;
                        $product->type = 'goods';
                        $product->status = 'active';
                        $product->unit_id = $unitType->id ?? null;
                        $product->save();

                        // Create purchase order item
                        $newItem = new PurchaseItem();
                        $newItem->purchase_order_id = $order->id;
                        $newItem->product_id = $product->id;
                        $newItem->department_id='1';
                        $newItem->budget_head_id = $budgetHeadId;
                        $newItem->budget_head = $finalBudgetHeadId;
                        $newItem->wbs = $wbsWithPrefix;
                        $newItem->item_name = $itemData['item_name'];
                        $newItem->quantity = $quantity;
                        $newItem->unit_id = $itemData['unit_id'] ?? null;
                        $newItem->unit_price = $unitPrice;
                        $newItem->retention_amount = $retention;
                        $newItem->retention = $retentionAmount;
                        $newItem->taxes_percent = $taxPercent;
                        $newItem->tax_ids = $gstid->id;
                        $newItem->gst_amount = $gstAmount;
                        $newItem->amount = $amountAfterRetention;
                        $newItem->type = 'item';
                        $newItem->save();

                        $updatedItemIds[] = $newItem->id;
                    }
                }
            }
        }

        // Delete items that were removed
        $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
        if (!empty($itemsToDelete)) {
            PurchaseItem::whereIn('id', $itemsToDelete)->delete();
        }

        // Update order totals
        $items = $order->items()->where('type', 'item')->get();
        $subtotal = $items->sum('amount');
        $totalTax = $items->sum('gst_amount');

        $order->sub_total = $subtotal;
        $order->total = $subtotal + $totalTax;
        $order->save();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => __('app.recordUpdated')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
    /**
     * Update the specified resource in storage.
     * @param StoreRequest $request
     * @param int $id
     * @return Renderable
     */


    public function storeEdit(Request $request){
        $data = $request->all();
        $pro = $request->project_id;
        $sub = $request->subproject_id;
        $project = DB::table('projects')
            ->where('id', $pro)
            ->select('project_short_code', 'client_id')
            ->first();

        $projectShortCode = $project->project_short_code ?? null;
        $clientId = $project->client_id ?? null;
        $clientUniqueId = DB::table('client_details')
                        ->where('user_id', $clientId)
                        ->value('client_unique_id');
        $budgetHeadId = DB::table('budget_head')
                        ->where('project_id', $pro)
                        ->where('sub_project_id', $sub)
                        ->value('id');
        $id= $request->po_wo_id;

        $order = PurchaseOrder::findOrFail($id);
         if ($request->purchase_order_number) {
            preg_match('/PL#(\d+)/', $request->purchase_order_number, $matches);
            if (isset($matches[1])) {
                $order->purchase_order_number = $matches[1];
            } else {
                $order->purchase_order_number = $request->purchase_order_number; // Save the original input
            }
        }
        $order->po_number = $request->po_number;
        $order->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->purchase_date)->format('Y-m-d');
        $order->expected_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');

        $order->reference_number = $request->reference_number;
        $order->po_number_type = $request->type;
        $order->validate ='false';
        $order->minimum_billing_limit = $request->minbilllimit ?? null;
        $order->billing_cycle = $request->billingcycle ?? null;
        $order->billing_duration = $request->billingDuration ?? null;
        $order->reserved_amt_in_percent = $request->reserveAmt ?? null;
        $order->action = 'edit';
        $order->save();
        $orders=$id;
        $poTypes=$request->order_type;

        $userId = auth()->id();
        DB::table('po_wo_approve_reject_history')->insert([
            'user_id'  => $userId,
            'po_wo_id' => $id,
            'reason'   => 'Work Order Re-Submitted for Approval'
        ]);

        if ($request->hasFile('import_file')) {
            try {

                $this->importFileProcessPo($request, POOrderImport::class, $orders);
                DB::table('purchase_items')->where('purchase_order_id',$orders)->update(['status' => 2]);
                $view = view('projects.ajax.po_import_progress', array_merge($this->data, [
                    'purchase_order_id' => $orders,
                    'orderId' => $orders,
                    'order_type' => $poTypes,
                    'action' => 'edit'
                ]))->render();
                return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
            } catch (\Exception $e) {
                return Reply::error($e->getMessage());
            }
        } else {
            if ($request->order_type == 'purchase_order') {
                return redirect()->route('purchase-order.index')
                                 ->with('success', __('messages.updateSuccess'));
            } elseif ($request->order_type == 'work_order') {
                return redirect()->route('work_order.work-order')
                                 ->with('success', __('messages.updateSuccess'));
            } else {
                return redirect()->back()
                                 ->with('success', __('messages.updateSuccess'));
            }
        }
    }
   public function update(Request $request)
        {
            $data = $request->all();

            $pro = $request->project_id;
                $sub = $request->subproject_id;

                $project = DB::table('projects')
                    ->where('id', $pro)
                    ->select('project_short_code', 'client_id')
                    ->first();

                $projectShortCode = $project->project_short_code ?? null;
                $clientId = $project->client_id ?? null;
                $clientUniqueId = DB::table('client_details')
                    ->where('user_id', $clientId)
                    ->value('client_unique_id');
                $budgetHeadId = DB::table('budget_head')
                    ->where('project_id', $pro)
                    ->where('sub_project_id', $sub)
                    ->value('id');

                    $id= $request->po_wo_id;
            $order = PurchaseOrder::findOrFail($id);

            if ($request->purchase_order_number) {
                preg_match('/PL#(\d+)/', $request->purchase_order_number, $matches);
                if (isset($matches[1])) {
                    $order->purchase_order_number = $matches[1];
                } else {
                    $order->purchase_order_number = $request->purchase_order_number; // Save the original input
                }
        }
        $order->currency_id = $request->currency_id ?? null;
        $ordercurrency_id = company()->currency_id;
        $order->exchange_rate = $request->exchange_rate;
        $order->vendor_id = $request->vendor_id ?? null;
        $order->project_id = $request->project_id;
        $order->subproject_id = $request->subproject_id;
        $order->po_number = $request->po_number;
        $order->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->purchase_date)->format('Y-m-d');
        $order->expected_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');
        $order_total = 0;
        $order->discount = 0;
        $order->calculate_tax = 'after_discount';
        $order->discount_type = 'percent';
        $order->order_type = $request->order_type;
        $order->company_group_id = $request->company_group_id;
        $order->billing_company_id = $request->billing_company_id;
        $order->reference_number = $request->reference_number;
        $order->category = $request->category;
        $order->po_number_type = $request->type;
        $order->delivery_status = 'in_transaction';
        $order->budgetHeadId = $budgetHeadId;
        $order->address_id = 1;
        $order->minimum_billing_limit = $request->minbilllimit ?? null;
        $order->billing_cycle = $request->billingcycle ?? null;
        $order->billing_duration = $request->billingDuration ?? null;
        $order->reserved_amt_in_percent = $request->reserveAmt ?? null;
        $order->save();
        $orders=$id;
        $poTypes=$request->order_type;
        // Process the file import if uploaded
       if ($request->hasFile('import_file')) {
            try {
                // Process the import file
                $this->importFileProcessPo($request, POOrderImport::class, $order->id);

                // Show import progress view
                $view = view('projects.ajax.po_import_progress', array_merge($this->data, [
                    'purchase_order_id' => $orders,
                    'orderId' => $orders,
                    'order_type' => $poTypes
                ]))->render();

                // Return success with view for file upload progress
                return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
            } catch (\Exception $e) {
                // Handle exception and return error
                return Reply::error($e->getMessage());
            }
            } else {
            // Checking the order type and redirecting accordingly
            if ($request->order_type == 'purchase_order') {
                // Redirect to the purchase order index with success message
                return redirect()->route('purchase-order.index')
                                ->with('success', __('messages.updateSuccess'));
            } elseif ($request->order_type == 'work_order') {
                // Redirect to the work order index with success message
                return redirect()->route('work_order.work-order')
                                ->with('success', __('messages.updateSuccess'));
            } else {
                // Default fallback redirect if none of the conditions match
                return redirect()->back()
                                ->with('success', __('messages.updateSuccess'));
            }
           }


    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->deletePermission = user()->permission('delete_invoices');
        abort_403(!(
            $this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $order->added_by == user()->id)
            || ($this->deletePermission == 'both' || $order->added_by == user()->id)
        ));

        PurchaseOrder::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));

    }

    public function addItem(Request $request)
    {
        $this->items = Product::findOrFail($request->id);

        $exchangeRate = Currency::findOrFail($request->currencyId);

        if (!is_null($exchangeRate) && !is_null($exchangeRate->exchange_rate)) {
            if ($this->items->total_amount != '') {
                /** @phpstan-ignore-next-line */
                $this->items->purchase_price = floor($this->items->total_amount * $exchangeRate->exchange_rate);
            }
            else {

                $this->items->purchase_price = floatval($this->items->purchase_price) * floatval($exchangeRate->exchange_rate);
            }
        }
        else {
            if ($this->items->total_amount != '') {
                $this->items->price = $this->items->total_amount;
            }
        }

        $this->items->price = number_format((float)$this->items->price, 2, '.', '');
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        $view = view('purchase::purchase-order.ajax.add_item', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function deletePurchaseItemImage(Request $request)
    {
        $item = PurchaseItemImage::where('purchase_item_id', $request->purchase_item_id)->first();

        if ($item) {
            Files::deleteFile($item->hashname, PurchaseItemImage::FILE_PATH . '/' . $item->id . '/');
            $item->delete();
        }

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function sendOrder($orderID)
    {
        $order = PurchaseOrder::with('vendor')->findOrFail($orderID);
        $notifyUser = $order->vendor;

        if (isset($notifyUser) && !is_null($notifyUser) && request()->data_type != 'mark_as_send') {
            event(new NewPurchaseOrderEvent($order, $notifyUser));
        }

        $order->send_status = 1;
        $order->save();

        if (request()->data_type == 'mark_as_send') {
            return Reply::success(__('purchase::messages.orderMarkAsSent'));
        }
        else {
            return Reply::success(__('purchase::messages.orderSentSuccessfully'));
        }
    }

    public function download($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->purchaseSetting = purchase_setting();
        $this->orderSetting = PurchaseSetting::first();

        $this->order = PurchaseOrder::with('vendor', 'items', 'items.unit')->findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_order');
        $this->company = $this->order->company;

        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->order->added_by == user()->id)
        ));

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        // Download file uploaded
        if ($this->order->file != null) {
            return response()->download(storage_path('app/public/purchase-order-files') . '/' . $this->order->file);
        }

        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForConsoleDownload($id)
    {
        $this->order = PurchaseOrder::with('items', 'items.unit')->findOrFail($id);

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);

            }
            else {
                $this->discount = $this->order->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->company = $this->order->company;

        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->defaultAddress = CompanyAddress::where('is_default', 1)->where('company_id', $this->order->company_id)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        $pdf->loadView('purchase::purchase-order.pdf.order-1', $this->data);

        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);

        $filename = $this->order->purchase_order_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function domPdfObjectForDownload($id)
        {
            $this->invoiceSetting = invoice_setting();
            $this->orderSetting = PurchaseSetting::first();

            $this->order = PurchaseOrder::with('items', 'items.unit')->findOrFail($id);

            App::setLocale($this->invoiceSetting->locale ?? 'en');
            Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

            if ($this->order->discount > 0) {
                if ($this->order->discount_type == 'percent') {
                    $this->discount = (($this->order->discount / 100) * $this->order->sub_total);
                }
                else {
                    $this->discount = $this->order->discount;
                }
            }
            else {
                $this->discount = 0;
            }

            $taxList = array();

            $items = PurchaseItem::with('itemTaxes')
                ->where('purchase_order_id', $this->order->id)
                ->get();

            foreach ($items as $item) {
                if (!is_null($item->itemTaxes)) {
                    foreach ($item->itemTaxes as $tax) {
                        $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                        if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                            if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                                $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                            }
                            else {
                                $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                            }
                        }
                        else {
                            if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                                $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                            }
                            else {
                                $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                            }
                        }
                    }
                }
            }

            $this->taxes = $taxList;

            $this->company = $this->order->company;
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true); // Make sure PHP in view is enabled
        $pdf->loadView('purchase::purchase-order.pdf.' . $this->invoiceSetting->template, $this->data)
            ->setPaper('a4', 'landscape');

        $filename = $this->order->purchase_order_number;

        // Get the canvas to draw page number
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('Helvetica', 'normal');
        $dompdf->render(); // Force render
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(720, 560, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, [0, 0, 0]);

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];

        }




    public function getExchangeRate($id)
    {
        $exchangeRate = Currency::where('id', $id)->pluck('exchange_rate')->toArray();

        return Reply::dataOnly(['status' => 'success', 'data' => $exchangeRate]);
    }

    public function history($id)
    {
        $tab = request('tab');

        $this->activeTab = $tab ?: 'history';

        $orderVariable = PurchaseOrderHistory::where('purchase_order_id', $id)
            ->selectRaw('purchase_vendor_id, purchase_order_id, "null" as purchase_bill_id, "null" as purchase_payment_id, "null" as purchase_credit_id, "null" as purchase_order, "null" as purchase_vendor_notes_id, "null" as bill_date, user_id, "null" as amount, label, details, purchase_order_histories.created_at, "Order" as type');
        $billVariable = DB::table('purchase_bill_histories')
            ->where('purchase_orders.id', $id)
            ->join('purchase_orders', 'purchase_orders.id', 'purchase_bill_histories.purchase_order_id')
            ->selectRaw('purchase_vendor_id, "null" as purchase_order_id, purchase_bill_id, "null" as purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, bill_date, user_id, amount, label, details, purchase_bill_histories.created_at, "bill" as type')
            ->where('purchase_bill_histories.company_id', company()->id);


        $paymentVariable = PurchasePaymentHistory::join('purchase_orders', 'purchase_orders.id', 'purchase_payment_histories.purchase_order_id')
            ->where('purchase_orders.id', $id)
            ->with('user')
            ->selectRaw('purchase_vendor_id, "null" as purchase_order_id, "null" as purchase_bill_id, purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, "null" as bill_date, user_id, amount, label, details, purchase_payment_histories.created_at, "Payment" as type')
            ->where('purchase_payment_histories.company_id', company()->id)
            ->union($orderVariable)
            ->union($billVariable)
            ->orderByDesc('created_at')->get();

        $this->history = $paymentVariable;
        $this->view = 'purchase::purchase-order.ajax.history';

        return view('purchase::purchase-order.show', $this->data);

    }

    public function vendorCurrency(Request $request)
    {
        if (!is_null($request->id)) {
            $PurchaseVendor = PurchaseVendor::with('currency')->findOrFail($request->id);

            return Reply::dataOnly(['data' => $PurchaseVendor->currency]);
        }
        else {
            return Reply::dataOnly(['data' => null]);
        }

    }

    public function changeStatus($id, Request $request)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->delivery_status = $request->delivery_status;
        $purchaseOrder->save();

        return Reply::success(__('messages.updateSuccess'));

    }

     public function work_order(WorkOrderDataTable $dataTable)
    {
        $viewPermission = user()->permission('work_order');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'purchase::app.menu.workOrder';

        return $dataTable->render('purchase::purchase-order.index_work', $this->data);
    }

  public function create_work()
    {
        $this->pageTitle = __('purchase::app.menu.workOrder');
        $this->lastOrder = PurchaseOrder::lastOrderNumber() + 1;
        $this->products = Product::where('status', 'active')->get();
        $this->categories = ProductCategory::all();
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        $this->vendors = PurchaseVendor::all();
        $this->addresses = CompanyAddress::all();
        $this->currencies = Currency::all();
        $this->projects = Project::all();
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->vendorID = request()->purchase_order_vendor_id;
        $this->purchaseVendorID = PurchaseVendor::where('id', $this->vendorID)->first();
        $this->company = DB::table('company_group')
                            ->where('status', 1)
                            ->select('company_name', 'id')
                            ->get();
        $this->zero = '';

        if (strlen($this->lastOrder) < $this->purchaseSetting->purchase_order_number_digit) {
            $condition = $this->purchaseSetting->purchase_order_number_digit - strlen($this->lastOrder);

            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $this->linkOrderPermission = user()->permission('link_order_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;

        if (request()->ajax()) {
            $html = view('purchase::purchase-order.ajax.create_work', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::purchase-order.ajax.create_work';

        return view('purchase::purchase-order.create', $this->data);

    }
     public function getRejectCommentWorkOrder($id)
    {
        $rejectComment = DB::table('po_wo_approve_reject_history as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->select('p.*', 'u.name as user_name')
            ->where('p.po_wo_id', $id)

            ->orderBy('p.created_at', 'desc')
            ->get();

        if ($rejectComment->isNotEmpty()) {
            $rejectComment->transform(function ($item) {
                $item->formatted_date = Carbon::parse($item->created_at)->format('M d, Y h:i A');
                return $item;
            });
            return response()->json(['status' => true, 'data' => $rejectComment]);
        } else {
            return response()->json(['status' => false, 'message' => 'No reject reasons found.']);
        }
    }
}
