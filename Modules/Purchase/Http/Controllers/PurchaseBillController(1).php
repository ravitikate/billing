<?php
namespace Modules\Purchase\Http\Controllers;

use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use App\Mail\DynamicEmail;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Models\PaymentGatewayCredentials;
use App\Models\Tax;
use App\Models\UnitType;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Purchase\DataTables\PurchaseBillApprovalDataTable;
use Modules\Purchase\DataTables\PurchaseBillDataTable;
use Modules\Purchase\DataTables\PurchaseBillDelayDataTable;
use Modules\Purchase\Entities\PurchaseBill;
use Modules\Purchase\Entities\PurchaseBillHistory;
use Modules\Purchase\Entities\PurchaseBillItem;
use Modules\Purchase\Entities\PurchaseItem;
use Modules\Purchase\Entities\PurchaseOrder;
use Modules\Purchase\Entities\PurchasePaymentHistory;
use Modules\Purchase\Entities\PurchaseSetting;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Events\NewPurchaseBillEvent;
use Modules\Purchase\Http\Requests\StoreBill;
use App\Notifications\SendInvoiceByContractor;use App\Models\User;

class PurchaseBillController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'purchase::app.menu.bill';

        $this->middleware(function ($request, $next) {
            abort_403(! in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(PurchaseBillDataTable $dataTable)
    {
        $this->viewBillPermission = user()->permission('view_bill');
        $this->addBillPermission  = user()->permission('add_bill');
        $this->vendors            = PurchaseVendor::with('currency')->get();

        $this->groups = DB::table('client_details')
            ->select('id', 'company_name')
            ->get();
        $this->projects = DB::table('projects')
            ->select('id', 'project_name')
            ->get();

        abort_403(! in_array($this->viewBillPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'purchase::app.menu.bills';

        return $dataTable->render('purchase::bills.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

        $this->pageTitle       = __('purchase::app.menu.createBill');
        $this->lastBill        = PurchaseBill::lastPurchaseBillNumber() + 1;
        $this->purchaseSetting = PurchaseSetting::first();
        $this->zero            = '';
        $this->purchaseVendors = PurchaseVendor::all();
        $this->units           = UnitType::all();
        $this->taxes           = Tax::all();
        $this->paymentGateway  = PaymentGatewayCredentials::first();
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->currencies      = Currency::all();
        $this->purchaseOrder   = [];
        $this->vendorID        = request()->bill_vendor_id;

        $userID                       = user()->id;
        $this->purchaseVendorsDetails = PurchaseVendor::where('user_id', $userID)->first();

        if (strlen($this->lastBill) < $this->purchaseSetting->bill_number_digit) {
            $condition = $this->purchaseSetting->bill_number_digit - strlen($this->lastBill);

            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        if (request('order') != '') {
            $this->purchaseOrderId = request()->order;
            $this->type            = 'order';
            $this->purchaseOrder   = PurchaseOrder::with('vendor')->find($this->purchaseOrderId);
        }
        if (request()->ajax()) {
            $html = view('purchase::bills.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::bills.ajax.create';

        return view('purchase::bills.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(StoreBill $request)
    {
        dd($request->type);
        $bill                       = new PurchaseBill();
        $bill->purchase_bill_number = $request->bill_number;
        $bill->purchase_vendor_id   = $request->vendor_id;
        $bill->bill_date            = Carbon::createFromFormat($this->company->date_format, $request->issue_date)->format('Y-m-d');
        $bill->purchase_order_id    = $request->purchase_order_id;
        $bill->company_id           = company()->id;
        // $bill->discount = $request->discount_value;
        $bill->sub_total = $request->sub_total;
        $bill->total     = $request->sub_total;
        // $bill->discount_type = $request->discount_type;
        $bill->status = $request->type;
        $bill->note   = $request->note;
        $bill->save();

        return reply::success(__('messages.recordSaved'));

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->viewBillPermission   = user()->permission('view_bill');
        $this->editBillPermission   = user()->permission('edit_bill');
        $this->deleteBillPermission = user()->permission('delete_bill');
        $this->purchaseBill         = PurchaseBill::with('purchaseVendor', 'order')->findOrFail($id);
        $this->orderCurrency        = PurchaseOrder::where('id', $this->purchaseBill->order->id)->first();
        abort_403(! ($this->viewBillPermission == 'all' || $this->viewBillPermission == 'added' && user()->id == $this->purchaseBill->added_by));

        //  \DB::enableQueryLog();
        $this->purchaseBillItem = PurchaseBillItem::with('bill')
            ->where('purchase_bills_id', $id)
            ->get();

        //   dd(\DB::getQueryLog());

        $this->purchaseOrder = PurchaseOrder::with('items', 'items.taxes', 'items.purchaseItemImage')->findOrFail($this->purchaseBill->purchase_order_id);

        $this->settings = company();

        $this->discount = 0;

        if ($this->purchaseOrder->discount > 0) {
            if ($this->purchaseOrder->discount_type == 'percent') {
                $this->discount = (($this->purchaseOrder->discount / 100) * $this->purchaseOrder->sub_total);
            } else {
                $this->discount = $this->purchaseOrder->discount;
            }
        }

        $taxList = [];

        foreach ($this->purchaseOrder->items as $item) {
            if (! is_null($item->taxes)) {
                foreach ($item->taxes as $tax) {
                    if (! isset($taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'])) {

                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100);

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $item->amount * ($tax->rate_percent / 100);
                        }

                    } else {
                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100));

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + ($item->amount * ($tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->view = 'purchase::bills.ajax.overview';
        $tab        = request('tab');
        
         $this->commentsHistory = DB::table('approval_bill_details as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->select('p.*', 'u.name as user_name')
            ->where('p.bill_id', $id)
            ->where('p.approval_status', 3)
            ->orderBy('p.created_at', 'desc')
            ->get();

        switch ($tab) {
            case 'history':
                return $this->history($id);
            default:
                $this->view = 'purchase::bills.ajax.overview';
                break;
        }

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab            = $tab ?: 'overview';
        $this->purchase_bills_files = DB::table('purchase_bills_files')->where('bill_id', $id)->get();

        return view('purchase::bills.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        // $this->editBillPermission = user()->permission('edit_bill');

        // abort_403(
        //     ! ($this->editBillPermission == 'all'
        //         || $this->editBillPermission == 'added' && user()->id == $this->purchaseBill->added_by)
        // );

        $this->purchaseBillId       = PurchaseBill::with('purchaseVendor', 'order')->findOrFail($id);
        $purchaseOrderId            = DB::table('purchase_bills')->where('id', $id)->first();
        $this->order_id             = $purchaseOrderId;
        $this->purchase_bills_files = DB::table('purchase_bills_files')->where('bill_id', $id)->get();
        $items                      = DB::table('purchase_items')
            ->where('purchase_order_id', $purchaseOrderId->purchase_order_id)
            ->select('product_id', 'quantity')
            ->orderBy('product_id')
            ->get()
            ->keyBy('product_id');

        $billDate = DB::table('purchase_bills')
            ->where('id', $id)
            ->select('bill_date')
            ->first();

        $this->bill_Date = Carbon::createFromFormat('Y-m-d', $billDate->bill_date)->format('d-m-Y');

        $totalQuantities = PurchaseBillItem::select('purchase_bill_items.*')
            ->join('purchase_bills', 'purchase_bill_items.purchase_bills_id', '=', 'purchase_bills.id')
            ->where('purchase_bills.id', $id)
            ->where('purchase_bill_items.company_id', auth()->user()->company_id)
            ->orderBy('purchase_bill_items.product_id')
            ->get();

        $totalBillQuantities = PurchaseBillItem::selectRaw('SUM(quantity) as billQuantity, product_id')
            ->where('purchase_bills_id', $id)
            ->groupBy('product_id') // important!
            ->orderBy('product_id')
            ->get()
            ->keyBy('product_id');

        $finalResult = $totalQuantities->map(function ($item) use ($items, $totalBillQuantities) {
            $productId            = $item->product_id;
            $item->total_quantity = isset($items[$productId]) ? $items[$productId]->quantity : 0;
            $item->bill_quantity  = isset($totalBillQuantities[$productId]) ? $totalBillQuantities[$productId]->billQuantity : 0;
            return $item;
        });

        $this->purchaseBill = $finalResult;
        $this->billID       = $id;

        $this->pageTitle       = 'Bill-' . $purchaseOrderId->id . ' - Edit';
        $this->zero            = '';
        $this->purchaseSetting = PurchaseSetting::first();
        if (request()->ajax()) {
            $html = view('purchase::bills.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::bills.ajax.edit';

        return view('purchase::bills.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        // print_r($data);exit;
        $po = DB::table('purchase_orders')->where('id', $request->purchase_order)->first();

        if (! $po) {
            return response()->json([
                'error' => 'Purchase order not found.',
            ], 404);
        }

        $minimum_billing_limit = $po->minimum_billing_limit;
        if ($request->total_amount < $minimum_billing_limit) {
            return Reply::dataOnly([
                'error' => "Total amount is below the minimum billing limit. Minimum allowed: $minimum_billing_limit",
            ]);
        }
        $updateBill = [
            'reference_bill_no'    => $request->reference_bill_no,
            'purchase_vendor_id'   => $request->vendor_id,
            'bill_date'            => Carbon::createFromFormat('D d M Y', $request->issue_date)->format('Y-m-d'),
            'purchase_order_id'    => $request->purchase_order,
            'sub_total'            => $request->sub_total,
            'tax_total'            => $request->total_tax,
            'total'                => $request->total_amount,
            'netpayableamount'     => $request->total_amount,
            'status'               => $request->type,
            'note'                 => $request->note,
            'added_by'             => user()->id,
            'company_id'           => company()->id,
            'updated_at'           => now(),
        ];

        DB::table('purchase_bills')->where('id', $id)->update($updateBill);

        if ($id && $request->hasFile('files')) {
            $files  = $request->file('files');
            $titles = $request->input('file_titles');

            foreach ($files as $index => $file) {
                $title        = $titles[$index] ?? null;
                $originalName = $file->getClientOriginalName();

                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file directly to public/user-uploads
                $destinationPath = public_path('bill-files');

// Check if directory exists; if not, create it
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true); // recursive = true
                }

// Now move the file
                $file->move($destinationPath, $filename);

                // ✅ Save record in DB
                DB::table('purchase_bills_files')->insert([
                    'bill_id'       => $id,
                    'original_name' => $originalName,
                    'stored_name'   => $filename, // store path
                    'custom_title'  => $title,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        if (isset($data['item_name']) && is_array($data['item_name'])) {
            foreach ($data['item_name'] as $index => $itemName) {
                $retention_cost = ($data['product_amount'][$index] * $data['retention_amount'][$index]) / 100;

                $billItemsUpdate = [
                    'purchase_bills_id' => $id,
                    'item_name'         => $itemName,
                    'item_summary'      => $data['item_summary'][$index] ?? '',
                    'type'              => 'bill_item',
                    'company_id'        => company()->id,
                    'wbs'               => $data['wbs'][$index] ?? '',
                    'item_id'           => $data['item_id'][$index] ?? '',
                    'retention_amount'  => $data['retention_amount'][$index] ?? '',
                    'retention_cost'    => $retention_cost ?? '',
                    'budget_head'       => $data['budget_head'][$index] ?? '',
                    'hsn_sac_code'      => $data['wbc'][$index] ?? '',
                    'product_id'        => $data['product_id'][$index] ?? null,
                    'quantity'          => $data['quantity'][$index] ?? 0,
                    'unit_id'           => $data['unit_id'][$index] ?? null,
                    'unit'              => $data['unit'][$index] ?? 0,
                    'unit_price'        => $data['cost_per_item'][$index] ?? 0,
                    'taxes_percent'     => $data['taxes'][$index] ?? 0,
                    'tax_amount'        => $data['tax_amount'][$index] ?? 0,
                    'amount'            => $data['product_amount'][$index] ?? 0,
                    'department_id'     => $data['department_id'][$index] ?? null,
                    'updated_at'        => now(),
                ];

                $row_id = $data['row_id'][$index];
                DB::table('purchase_bill_items')->where('id', $row_id)->update($billItemsUpdate);
            }
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function vendorPurchaseOrders()
    {
        $purchaseOrderId = PurchaseBill::pluck('purchase_order_id');
        // $purchaseOrder   = PurchaseOrder::whereNotIn('id', $purchaseOrderId)->where('vendor_id', request()->vendor_id)->get();
        $purchaseOrder = PurchaseOrder::where('vendor_id', request()->vendor_id)->where('status', true)->where('validate', true)->get();

        return Reply::dataOnly(['data' => $purchaseOrder]);
    }

    public function purchaseOrderProducts()
    {
        // Clear previous data
        $this->purchaseOrder = null;
        $this->purchaseItems = [];

        // Validate and fetch the purchase order
        $purchaseOrderId     = request()->purchase_order_id;
        $this->purchaseOrder = PurchaseOrder::find($purchaseOrderId);

        if (! $this->purchaseOrder) {
            return response()->json(['error' => 'Purchase order not found'], 404);
        }

        $poType = $this->purchaseOrder->type === 'stages' ? 'stages' : 'boq';

        $totalQuantities = PurchaseBillItem::select('purchase_bill_items.product_id', DB::raw('SUM(purchase_bill_items.quantity) as total_quantity'))
            ->join('purchase_bills', 'purchase_bill_items.purchase_bills_id', '=', 'purchase_bills.id')
            ->where('purchase_bills.purchase_order_id', $purchaseOrderId)
            ->where('purchase_bill_items.company_id', auth()->user()->company_id)
            ->groupBy('purchase_bill_items.product_id')
            ->pluck('total_quantity', 'product_id')
            ->toArray();

        $this->purchaseItems = PurchaseItem::with(['unit', 'taxes', 'purchaseOrder', 'purchaseItemImage'])
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('po_type', $poType)
            ->get()
            ->map(function ($item) use ($totalQuantities) {
                $item->total_quantity = isset($totalQuantities[$item->product_id]) ? (float) $totalQuantities[$item->product_id] : 0.00;
                return $item;
            });

        // Check if purchase items exist
        if ($this->purchaseItems->isEmpty()) {
            return response()->json(['error' => 'No purchase items found for the selected order'], 404);
        }

        // Render the view
        $view = view('purchase::bills.ajax.add_item', [
            'purchaseItems' => $this->purchaseItems,
            'purchaseOrder' => $this->purchaseOrder,
        ])->render();
        return response()->json(['view' => $view]);
    }

    public function sendBill($billId)
    {
        $purchaseBill = PurchaseBill::with(['purchaseVendor'])->findOrFail($billId);

        if ($purchaseBill->purchase_vendor_id != null && $purchaseBill->purchase_vendor_id != '') {
            $notifyUser = $purchaseBill->purchaseVendor;
        }

        if (isset($notifyUser) && ! is_null($notifyUser)) {
            event(new NewPurchaseBillEvent($purchaseBill, $notifyUser));
        }

        return Reply::success(__('purchase::messages.billSentSuccessfully'));

    }

    public function download($id)
    {
        $this->invoiceSetting  = invoice_setting();
        $this->purchaseSetting = PurchaseSetting::first();

        $this->purchaseBill   = PurchaseBill::findOrFail($id);
        $this->viewPermission = user()->permission('view_bill');

        abort_403(! (
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->purchaseBill->added_by == user()->id)
        ));

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        $pdfOption = $this->domPdfObjectForDownload($id, $this->purchaseBill);
        $pdf       = $pdfOption['pdf'];
        $filename  = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForConsoleDownload($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->purchaseBill   = PurchaseBill::findOrFail($id);
        $this->purchaseOrder  = PurchaseOrder::with('items', 'items.unit')->findOrFail($this->purchaseBill->purchase_order_id);

        if ($this->purchaseOrder->discount > 0) {
            if ($this->purchaseOrder->discount_type == 'percent') {
                $this->discount = (($this->purchaseOrder->discount / 100) * $this->purchaseOrder->sub_total);

            } else {
                $this->discount = $this->purchaseOrder->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = [];

        foreach ($this->purchaseOrder->items as $item) {
            if (! is_null($item->taxes)) {
                foreach ($item->taxes as $tax) {
                    if (! isset($taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'])) {

                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100);

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $item->amount * ($tax->rate_percent / 100);
                        }

                    } else {
                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100));

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + ($item->amount * ($tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->company = $this->purchaseOrder->company;

        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->defaultAddress = CompanyAddress::where('is_default', 1)->where('company_id', $this->order->company_id)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        $pdf->loadView('purchase::bills.pdf.' . $this->invoiceSetting->template, $this->data);

        $dom_pdf = $pdf->getDomPDF();
        $canvas  = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);

        $filename = $this->purchaseBill->bill_number;

        return [
            'pdf'      => $pdf,
            'fileName' => $filename,
        ];
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoiceSetting  = invoice_setting();
        $this->purchaseSetting = PurchaseSetting::first();
        $this->purchaseBill    = PurchaseBill::findOrFail($id);
        $this->purchaseOrder   = PurchaseOrder::with('items', 'items.taxes', 'items.purchaseItemImage')->findOrFail($this->purchaseBill->purchase_order_id);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        if ($this->purchaseOrder->discount > 0) {
            if ($this->purchaseOrder->discount_type == 'percent') {
                $this->discount = (($this->purchaseOrder->discount / 100) * $this->purchaseOrder->sub_total);

            } else {
                $this->discount = $this->purchaseOrder->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = [];

        foreach ($this->purchaseOrder->items as $item) {
            if (! is_null($item->taxes)) {
                foreach ($item->taxes as $tax) {
                    if (! isset($taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'])) {

                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100);

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $item->amount * ($tax->rate_percent / 100);
                        }

                    } else {
                        if ($this->purchaseOrder->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->purchaseOrder->sub_total) * $this->discount) * ($tax->rate_percent / 100));

                        } else {
                            $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] = $taxList[$tax->tax_name . ': ' . $tax->rate_percent . '%'] + ($item->amount * ($tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->company = $this->purchaseOrder->company;

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('purchase::bills.pdf.' . $this->invoiceSetting->template, $this->data);
        $filename = $this->purchaseBill->bill_number;

        return [
            'pdf'      => $pdf,
            'fileName' => $filename,
        ];
    }

    public function history($id)
    {
        $tab = request('tab');

        $this->activeTab = $tab ?: 'history';

        $billVariable = PurchaseBillHistory::where('purchase_bill_id', $id)
            ->selectRaw('purchase_order_id, purchase_bill_id, "null" as purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, bill_date, user_id, amount, label, details, purchase_bill_histories.created_at, "bill" as type');
        $paymentVariable = PurchasePaymentHistory::join('purchase_bills', 'purchase_bills.id', 'purchase_payment_histories.purchase_bill_id')
            ->where('purchase_bills.id', $id)
            ->with('user')
            ->selectRaw('"null" as purchase_order_id, purchase_bill_id, purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, "null" as bill_date, user_id, amount, label, details, purchase_payment_histories.created_at, "Payment" as type')
            ->where('purchase_payment_histories.company_id', company()->id)
            ->union($billVariable)
            ->orderByDesc('created_at')->get();

        $this->history = $paymentVariable;
        $this->view    = 'purchase::bills.ajax.history';

        return view('purchase::bills.show', $this->data);
    }

    public function purchaseOrderProductsList()
    {
        $this->purchaseOrder = PurchaseOrder::where('id', request()->purchase_order_id)->first();
        $this->purchaseItems = PurchaseItem::with('unit', 'taxes', 'purchaseOrder', 'purchaseItemImage')->where('purchase_order_id', request()->purchase_order_id)->get();
        // $view = view('purchase::bills.ajax.add_item', $this->data)->render();

        return reply::dataOnly(['status' => 'success', 'data' => $this->data]);
    }
    public function purchaseOrderProductsItem()
    {
        $this->purchaseItems = PurchaseItem::with('unit', 'taxes', 'purchaseOrder', 'purchaseItemImage')->where('id', request()->purchase_product_id)->first();
        $departmentDetails   = DB::table('teams')->where('id', $this->purchaseItems->department_id)->first();
        return reply::dataOnly(['status' => 'success', 'data' => $this->purchaseItems, 'departmentDetails' => $departmentDetails]);
    }
    // public function billstore(StoreBill $request)
    // {
    //     $data = $request->all();

    //     $billId = DB::table('purchase_bills')->insertGetId([
    //         'purchase_bill_number' => $request->bill_number,
    //         'purchase_vendor_id'   => $request->vendor_id,
    //         'bill_date'            => Carbon::createFromFormat('d-m-Y', $request->issue_date)->format('Y-m-d'),
    //         'purchase_order_id'    => $request->purchase_order_id,

    //         'sub_total'            => $request->sub_total,
    //         'tax_total'            => $request->tax_total,
    //         'total'                => $request->total_amount,
    //         'status'               => $request->type,
    //         'note'                 => $request->note,
    //         'added_by'             => user()->id,
    //         'company_id'           => company()->id,
    //         'created_at'           => now(),
    //         'updated_at'           => now(),
    //     ]);

    //     if ($billId && isset($data['item_name']) && is_array($data['item_name'])) {
    //         foreach ($data['item_name'] as $index => $itemName) {

    //             $filePath = null;
    //             if (isset($data['order_item_image1'][$index]) && ! empty($data['order_item_image1'][$index])) {
    //                 $filePath = $data['order_item_image1'][$index]; // Use existing image
    //             } elseif ($request->hasFile("order_item_image.$index")) {
    //                 // Upload new image if no existing image is provided
    //                 $file     = $request->file("order_item_image.$index");
    //                 $filePath = $file->store('contract_items', 'public');
    //             }
    //             $retention_cost = ( $data['product_amount'][$index] * $data['retention_amount'][$index] ) /100 ;

    //             // Insert into purchase_bill_items table
    //             $poBillItemId = DB::table('purchase_bill_items')->insertGetId([
    //                 'purchase_bills_id' => $billId,
    //                 'item_name'         => $itemName,
    //                 'budget_head_id'       =>  $request->budget_head_id,
    //                 'item_summary'      => $data['item_summary'][$index] ?? '',
    //                 'type'              => 'bill_item',
    //               'company_id'         => company()->id,
    //                 'wbs'               => $data['wbs'][$index] ?? '',
    //                  'item_id'           =>$data['item_id'][$index] ?? '',
    //               'retention_amount'    =>$data['retention_amount'][$index] ?? '',
    //               'retention_cost'    =>$retention_cost ?? '',
    //                 'budget_head' =>$data['budget_head'][$index] ?? '',
    //                 'hsn_sac_code'      => $data['wbc'][$index] ?? '',
    //                 'product_id'        => $data['product_id'][$index] ?? null,
    //                 'quantity'          => $data['quantity'][$index] ?? 0,
    //                 'unit_price'        => $data['cost_per_item'][$index] ?? 0,
    //                 'taxes_percent'     => $data['taxes'][$index] ?? 0,
    //                 'tax_amount'        => $data['tax_amount'][$index] ?? 0,
    //                 'amount'            => $data['product_amount'][$index] ?? 0,
    //                 'department_id'     => $data['department_id'][$index] ?? null,
    //                 'bill_item_file'    => $filePath,
    //                 'created_at'        => now(),
    //                 'updated_at'        => now(),
    //             ]);

    //             $subquery = DB::table('purchase_bill_items as pbi')
    //                 ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
    //                 ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
    //                 ->where('pbi.id', $poBillItemId) // Make sure poBillItemId is correctly used
    //                 ->select('pbi.department_id', 'po.project_id');

    //             $results1 = DB::table('priority_role as pr')
    //                 ->joinSub($subquery, 'subquery', function ($join) {
    //                     $join->on('pr.department_id', '=', 'subquery.department_id')
    //                         ->on('pr.project_id', '=', 'subquery.project_id');
    //                 })
    //                 ->where('pr.priority_id', 1)
    //                 ->select('pr.*')
    //                 ->get();

    //             //   echo "<pre>";
    //             //             print_r($results1);exit;

    //             if (! $results1->isEmpty()) {
    //                 foreach ($results1 as $result) {
    //                     DB::table('approval_items_by')->insert([
    //                         'bill_item_id'      => $poBillItemId,
    //                         // 'user_id'           => $result->user_id,
    //                         'department_id'     => $result->department_id,
    //                         'approve_status_id' => 1,
    //                     ]);

    //                     //     $userID       = $result->user_id;
    //                     //     $priorityID   = $result->priority_id;
    //                     //     $departmentID = $result->department_id;
    //                     //     $projectID    = $result->project_id;

    //                     //     $employeeDetail = DB::table('users')->where('id', $userID)->first();
    //                     //     $clientDetail   = DB::table('users')->where('id', $client_id)->first();

    //                     //     $receive_email   = $employeeDetail->email;
    //                     //     $email_templates = DB::table('email_templates')->where('template_key', 'invoice_send_by_client')->first();

    //                     //     if (! $email_templates) {
    //                     //         return response()->json(['success' => false, 'message' => 'Email template not found.']);
    //                     //     }

    //                     //     $dynamicData = [
    //                     //         'employee_name'  => $employeeDetail->name,
    //                     //         'invoice_number' => $request->bill_number,
    //                     //         'issue_date'     => $issue_date,
    //                     //         'due_date'       => $due_date,
    //                     //         'client_name'    => $clientDetail->name,
    //                     //         'item_name'      => $request->input("item_name.$key"),
    //                     //         'taxes'          => $request->input("tax.$key"),
    //                     //         'item_summary'   => $request->input("item_summary.$key"),
    //                     //         'quantity'       => $request->input("quantity.$key"),
    //                     //         'unit_price'     => $request->input("cost_per_item.$key"),
    //                     //         'amount'         => $request->input("amount.$key"),
    //                     //     ];
    //                     // // echo "<pre>";
    //                     // //                 print_r($dynamicData);exit;
    //                     //     $subject  = $email_templates->subject;
    //                     //     $template = $email_templates->template;
    //                     //     foreach ($dynamicData as $key => $value) {
    //                     //         $subject  = str_replace("[$key]", $value, $subject);
    //                     //         $template = str_replace("[$key]", $value, $template);
    //                     //     }

    //                     //     // Prepare the email data
    //                     //     $emailData = [
    //                     //         'subject'    => $subject,
    //                     //         'greeting'   => 'Hi there!',
    //                     //         'message'    => $template,
    //                     //         'closing'    => 'We appreciate your support!',
    //                     //         'salutation' => 'Cheers, The Team',
    //                     //     ];

    //                     //     try {
    //                     //         Notification::route('mail', $employeeDetail->email)
    //                     //             ->notify(new SendInvoiceByContractor($emailData));
    //                     //     } catch (\Exception $e) {
    //                     //         return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
    //                     //     }
    //                 }
    //             }

    //         }
    //     }

    //     return Reply::redirect(route('contracts.index'), __('messages.recordSaved'));
    // }

//     public function billstore(StoreBill $request)
//     {
//         $data = $request->all();

//         // print_r($data);exit;
//         // $bill_date = Carbon::createFromFormat('d-m-Y', $request->issue_date)->format('Y-m-d');
//         $bill_date = Carbon::createFromFormat('D d M Y', $request->issue_date)->format('Y-m-d');

//         $po = DB::table('purchase_orders')->where('id', $request->purchase_order_id)->first();

//         if (! $po) {
//             return response()->json([
//                 'error' => 'Purchase order not found.',
//             ], 404);
//         }

//         $minimum_billing_limit = $po->minimum_billing_limit;
//         if ($request->total_amount < $minimum_billing_limit) {
//             return Reply::dataOnly([
//                 'error' => "Total amount is below the minimum billing limit. Minimum allowed: $minimum_billing_limit",
//             ]);
//         }

//         $billing_cycle = $po->billing_cycle;

//         $latestBill = DB::table('purchase_bills')
//             ->where('purchase_order_id', $request->purchase_order_id)
//             ->orderBy('id', 'desc')
//             ->first();
//         $currentDate = Carbon::now();
//         if ($latestBill) {
//             $createdAt       = Carbon::parse($latestBill->created_at);
//             $nextBillingDate = $createdAt->copy()->addDays($billing_cycle);
//             if ($currentDate < $nextBillingDate) {
//                 return Reply::dataOnly([
//                     'error' => 'The biling date does not align with the billing cycle. Next allowed billing date: ' . $nextBillingDate->toDateString(),
//                 ]);
//             }
//         }
//         $billId = DB::table('purchase_bills')->insertGetId([
//             'reference_bill_no'    => $request->reference_bill_no,
//             'purchase_bill_number' => $request->bill_number,
//             'purchase_vendor_id'   => $request->vendor_id,
//             'bill_date'            => $bill_date,
//             'purchase_order_id'    => $request->purchase_order_id,
//             'sub_total'            => $request->sub_total,
//             'tax_total'            => $request->tax_total,
//             'total'                => $request->total_amount,
//             'netpayableamount'     => $request->total_amount,
//             'status'               => $request->type,
//             'note'                 => $request->note,
//             'added_by'             => user()->id,
//             'company_id'           => company()->id,
//             'created_at'           => now(),
//             'updated_at'           => now(),
//         ]);

//         if ($billId && $request->hasFile('files')) {
//             $files  = $request->file('files');
//             $titles = $request->input('file_titles');

//             foreach ($files as $index => $file) {
//                 $title        = $titles[$index] ?? null;
//                 $originalName = $file->getClientOriginalName();

//                 // Generate unique filename
//                 $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

//                 // Move file directly to public/user-uploads
//                 $destinationPath = public_path('bill-files');

// // Check if directory exists; if not, create it
//                 if (! file_exists($destinationPath)) {
//                     mkdir($destinationPath, 0755, true); // recursive = true
//                 }

// // Now move the file
//                 $file->move($destinationPath, $filename);

//                 // ✅ Save record in DB
//                 DB::table('purchase_bills_files')->insert([
//                     'bill_id'       => $billId,
//                     'original_name' => $originalName,
//                     'stored_name'   => $filename, // store path
//                     'custom_title'  => $title,
//                     'created_at'    => now(),
//                     'updated_at'    => now(),
//                 ]);
//             }
//         }

//         if ($billId) {
//             DB::transaction(function () use ($po, $billId) {
//                 $instantRecords = DB::table('instant_debit')
//                     ->where([
//                         'project_id'    => $po->project_id,
//                         'subproject_id' => $po->subproject_id,
//                         'vendor_id'     => $po->vendor_id,
//                         'po_id'         => $po->id,
//                         'bill_id'       => null,
//                         'bill_status'   => 1,
//                     ])
//                     ->get();

//                 foreach ($instantRecords as $record) {
//                     // Update instant_debit record
//                     DB::table('instant_debit')->where('id', $record->id)->update([
//                         'bill_id'     => $billId,
//                         'bill_status' => 2,
//                     ]);

//                     // Update related history records
//                     DB::table('instant_debit_history')
//                         ->where('instant_debit_id', $record->id)
//                         ->update([
//                             'bill_id' => $billId,
//                         ]);
//                 }
//             });
//         }

//         if ($billId && isset($data['item_name']) && is_array($data['item_name'])) {
//             foreach ($data['item_name'] as $index => $itemName) {

//                 $filePath = null;
//                 if (isset($data['order_item_image1'][$index]) && ! empty($data['order_item_image1'][$index])) {
//                     $filePath = $data['order_item_image1'][$index]; // Use existing image
//                 } elseif ($request->hasFile("order_item_image.$index")) {
//                     // Upload new image if no existing image is provided
//                     $file     = $request->file("order_item_image.$index");
//                     $filePath = $file->store('contract_items', 'public');
//                 }
//                 $retention_cost = ($data['product_amount'][$index] * $data['retention_amount'][$index]) / 100;

//                 // Insert into purchase_bill_items table
//                 $poBillItemId = DB::table('purchase_bill_items')->insertGetId([
//                     'purchase_bills_id' => $billId,
//                     'item_name'         => $itemName,
//                     'budget_head_id'    => $request->budget_head_id,
//                     'item_summary'      => $data['item_summary'][$index] ?? '',
//                     'type'              => 'bill_item',
//                     'company_id'        => company()->id,
//                     'purchase_order_id' => $request->purchase_order_id,
//                     'wbs'               => $data['wbs'][$index] ?? '',
//                     'item_id'           => $data['item_id'][$index] ?? '',
//                     'retention_amount'  => $data['retention_amount'][$index] ?? '',
//                     'retention_cost'    => $retention_cost ?? '',
//                     'budget_head'       => $data['budget_head'][$index] ?? '',
//                     'hsn_sac_code'      => $data['wbc'][$index] ?? '',
//                     'product_id'        => $data['product_id'][$index] ?? null,
//                     'quantity'          => $data['quantity'][$index] ?? 0,
//                     'unit_price'        => $data['cost_per_item'][$index] ?? 0,
//                     'taxes_percent'     => $data['taxes'][$index] ?? 0,
//                     'tax_amount'        => $data['tax_amount'][$index] ?? 0,

//                     'unit_id'           => $data['unit_id'][$index] ?? 0,
//                     'unit'              => $data['unit'][$index] ?? 0,

//                     'amount'            => $data['product_amount'][$index] ?? 0,
//                     'department_id'     => $data['department_id'][$index] ?? null,
//                     'bill_item_file'    => $filePath,
//                     'created_at'        => now(),
//                     'updated_at'        => now(),
//                 ]);
//             }

//             $poDetails    = DB::table('purchase_orders')->where('id', $request->purchase_order_id)->first();
//             $projectID    = $poDetails->project_id;
//             $subprojectID = $poDetails->subproject_id;

//             $p_role = DB::table('priority_role')->where('project_id', $projectID)->where('priority_id', 1)->first();

//             $userID     = $p_role->user_id;
//             $priorityID = $p_role->priority_id;

//             $employeeDetail = DB::table('users')->where('id', $userID)->first();
//             $vDetail        = DB::table('purchase_vendors')->where('id', $request->vendor_id)->first();
//             $vendorDetail   = DB::table('users')->where('id', $vDetail->user_id)->first();

//             $project      = DB::table('projects')->where('id', $projectID)->first();
//             $project_name = $project ? $project->project_name : null;

//             $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
//             $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

//             $email_templates = DB::table('email_templates')->where('template_key', 'bill_send_by_vendor')->first();

//             if (! $email_templates) {
//                 return response()->json(['success' => false, 'message' => 'Email template not found.']);
//             }

//             $dynamicData = [
//                 'employee_name' => $employeeDetail->name,
//                 'bill_no'       => 'Bill#' . str_pad($billId, 3, '0', STR_PAD_LEFT),
//                 'bill_date'     => $bill_date,
//                 'vendor_name'   => $vendorDetail->name,
//                 'sub_total'     => $request->sub_total,
//                 'tax_total'     => $request->tax_total,
//                 'total'         => $request->total_amount,
//             ];
//             // echo "<pre>";
//             // print_r($dynamicData);exit;
//             $subject  = $email_templates->subject;
//             $template = $email_templates->template;

//             foreach ($dynamicData as $key => $value) {
//                 $subject  = str_replace("[$key]", $value, $subject);
//                 $template = str_replace("[$key]", $value, $template);
//             }

//             // Prepare the email data
//             $emailData = [
//                 'subject' => $subject,
//                 'message' => $template,
//             ];
//             // print_r($employeeDetail->email);exit;

//             try {
//                 if (! empty($employeeDetail->email)) {
//                     // Notification::route('mail', $employeeDetail->email)
//                     //     ->notify(new SendInvoiceByContractor($emailData));
//                     Mail::to($employeeDetail->email)->send(new DynamicEmail($emailData));
                    
//                     $employee = User::find($employeeDetail->id); // only for notify
//                     if ($employee) {
//                         $employee->notify(new SendInvoiceByContractor([
//                             'bill_id' => $billId,
//                             'bill_no' => $dynamicData['bill_no'],
//                             'total'   => $dynamicData['total'],
//                     'title'       => 'New bill creat',
//                             'message' => 'New bill created by ' . $dynamicData['vendor_name'] .
//                             ' on ' . $dynamicData['bill_date'],
//                         ]));
//                     }
//                 }

//                 // Send email to Vendor if email exists
//                 // if (! empty($vendorDetail->email)) {
//                 //     // Notification::route('mail', $vendorDetail->email)
//                 //     //     ->notify(new SendInvoiceByContractor($emailData));
//                 //                              Mail::to($vendorDetail->email)->send(new DynamicEmail($emailData));
//                 // }

//             } catch (\Exception $e) {
//                 return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
//             }

//         }
//         return Reply::dataOnly(['status' => 'success']);
//     }

    public function billstore(StoreBill $request)
    {
        $data = $request->all();

        // print_r($data);exit;
        // $bill_date = Carbon::createFromFormat('d-m-Y', $request->issue_date)->format('Y-m-d');
        $bill_date = Carbon::createFromFormat('D d M Y', $request->issue_date)->format('Y-m-d');

        $po = DB::table('purchase_orders')->where('id', $request->purchase_order_id)->first();

        if (! $po) {
            return response()->json([
                'error' => 'Purchase order not found.',
            ], 404);
        }

        $minimum_billing_limit = $po->minimum_billing_limit;
        if ($request->total_amount < $minimum_billing_limit) {
            return Reply::dataOnly([
                'error' => "Total amount is below the minimum billing limit. Minimum allowed: $minimum_billing_limit",
            ]);
        }

        $billing_cycle = $po->billing_cycle;

        $latestBill = DB::table('purchase_bills')
            ->where('purchase_order_id', $request->purchase_order_id)
            ->orderBy('id', 'desc')
            ->first();
        $currentDate = Carbon::now();
        if ($latestBill) {
            $createdAt       = Carbon::parse($latestBill->created_at);
            $nextBillingDate = $createdAt->copy()->addDays($billing_cycle);
            if ($currentDate < $nextBillingDate) {
                return Reply::dataOnly([
                    'error' => 'The biling date does not align with the billing cycle. Next allowed billing date: ' . $nextBillingDate->toDateString(),
                ]);
            }
        }
        $billId = DB::table('purchase_bills')->insertGetId([
            'reference_bill_no'    => $request->reference_bill_no,
            'purchase_bill_number' => $request->bill_number,
            'purchase_vendor_id'   => $request->vendor_id,
            'bill_date'            => $bill_date,
            'purchase_order_id'    => $request->purchase_order_id,
            'sub_total'            => $request->sub_total,
            'tax_total'            => $request->total_tax,
            'total'                => $request->total_amount,
            'netpayableamount'     => $request->total_amount,
            'status'               => $request->type,
            'note'                 => $request->note,
            'added_by'             => user()->id,
            'company_id'           => company()->id,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        if ($billId && $request->hasFile('files')) {
            $files  = $request->file('files');
            $titles = $request->input('file_titles');

            foreach ($files as $index => $file) {
                $title        = $titles[$index] ?? null;
                $originalName = $file->getClientOriginalName();

                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file directly to public/user-uploads
                $destinationPath = public_path('bill-files');

                // Check if directory exists; if not, create it
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true); // recursive = true
                }

                // Now move the file
                $file->move($destinationPath, $filename);

                // ✅ Save record in DB
                DB::table('purchase_bills_files')->insert([
                    'bill_id'       => $billId,
                    'original_name' => $originalName,
                    'stored_name'   => $filename, // store path
                    'custom_title'  => $title,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        if ($billId) {
            DB::transaction(function () use ($po, $billId) {
                $instantRecords = DB::table('instant_debit')
                    ->where([
                        'project_id'    => $po->project_id,
                        'subproject_id' => $po->subproject_id,
                        'vendor_id'     => $po->vendor_id,
                        'po_id'         => $po->id,
                        'bill_id'       => null,
                        'bill_status'   => 1,
                    ])
                    ->get();

                foreach ($instantRecords as $record) {
                    // Update instant_debit record
                    DB::table('instant_debit')->where('id', $record->id)->update([
                        'bill_id'     => $billId,
                        'bill_status' => 2,
                    ]);

                    // Update related history records
                    DB::table('instant_debit_history')
                        ->where('instant_debit_id', $record->id)
                        ->update([
                            'bill_id' => $billId,
                        ]);
                }
            });

            $bill_details = DB::table('purchase_bills')->where('id', $billId)->first();

            $totalInstaAmount = DB::table('instant_debit')
                ->where('bill_id', $billId)
                ->sum('instantdebitamount');
            $totalInstaAmount = $totalInstaAmount ?? 0;

            $totalRetention = DB::table('purchase_bill_items')
                ->where('purchase_bills_id', $billId)
                ->sum('retention_cost');
            $totalRetention = $totalRetention ?? 0;

            $netpayableamt = $bill_details->total - ($bill_details->HoldBillAmt + $bill_details->AdvanceRecovery + $bill_details->Steel + $bill_details->Cement + $bill_details->Electricity + $bill_details->OtherDebits + $bill_details->SafetyDebit + $bill_details->QualityDebit + $bill_details->Housekeeping + $bill_details->PoorPerformance + $totalInstaAmount + $totalRetention);

            DB::table('purchase_bills')->where('id', $billId)->update(
                ['netpayableamount'    => $netpayableamt,
                    'instant_debit_amount' => $totalInstaAmount,
                    'retention_cost_amt'       => $totalRetention,
                ]);

        }

        if ($billId && isset($data['item_name']) && is_array($data['item_name'])) {
            foreach ($data['item_name'] as $index => $itemName) {

                $filePath = null;
                if (isset($data['order_item_image1'][$index]) && ! empty($data['order_item_image1'][$index])) {
                    $filePath = $data['order_item_image1'][$index]; // Use existing image
                } elseif ($request->hasFile("order_item_image.$index")) {
                    // Upload new image if no existing image is provided
                    $file     = $request->file("order_item_image.$index");
                    $filePath = $file->store('contract_items', 'public');
                }
                $retention_cost = ($data['product_amount'][$index] * $data['retention_amount'][$index]) / 100;

                // Insert into purchase_bill_items table
                $poBillItemId = DB::table('purchase_bill_items')->insertGetId([
                    'purchase_bills_id' => $billId,
                    'item_name'         => $itemName,
                    'budget_head_id'    => $request->budget_head_id,
                    'item_summary'      => $data['item_summary'][$index] ?? '',
                    'type'              => 'bill_item',
                    'company_id'        => company()->id,
                    'purchase_order_id' => $request->purchase_order_id,
                    'wbs'               => $data['wbs'][$index] ?? '',
                    'item_id'           => $data['item_id'][$index] ?? '',
                    'retention_amount'  => $data['retention_amount'][$index] ?? '',
                    'retention_cost'    => $retention_cost ?? '',
                    'budget_head'       => $data['budget_head'][$index] ?? '',
                    'hsn_sac_code'      => $data['wbc'][$index] ?? '',
                    'product_id'        => $data['product_id'][$index] ?? null,
                    'quantity'          => $data['quantity'][$index] ?? 0,
                    'unit_price'        => $data['cost_per_item'][$index] ?? 0,
                    'taxes_percent'     => $data['taxes'][$index] ?? 0,
                    'tax_amount'        => $data['tax_amount'][$index] ?? 0,

                    'unit_id'           => $data['unit_id'][$index] ?? 0,
                    'unit'              => $data['unit'][$index] ?? 0,

                    'amount'            => $data['product_amount'][$index] ?? 0,
                    'department_id'     => $data['department_id'][$index] ?? null,
                    'bill_item_file'    => $filePath,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            $poDetails    = DB::table('purchase_orders')->where('id', $request->purchase_order_id)->first();
            $projectID    = $poDetails->project_id;
            $subprojectID = $poDetails->subproject_id;

            $p_role = DB::table('priority_role')->where('project_id', $projectID)->where('priority_id', 1)->first();

            $userID     = $p_role->user_id;
            $priorityID = $p_role->priority_id;

            $employeeDetail = DB::table('users')->where('id', $userID)->first();
            $vDetail        = DB::table('purchase_vendors')->where('id', $request->vendor_id)->first();
            $vendorDetail   = DB::table('users')->where('id', $vDetail->user_id)->first();

            $project      = DB::table('projects')->where('id', $projectID)->first();
            $project_name = $project ? $project->project_name : null;

            $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
            $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

            $email_templates = DB::table('email_templates')->where('template_key', 'bill_send_by_vendor')->first();

            if (! $email_templates) {
                return response()->json(['success' => false, 'message' => 'Email template not found.']);
            }

            $dynamicData = [
                'employee_name' => $employeeDetail->name,
                'bill_no'       => 'Bill#' . str_pad($billId, 3, '0', STR_PAD_LEFT),
                'bill_date'     => $bill_date,
                'vendor_name'   => $vendorDetail->name,
                'sub_total'     => $request->sub_total,
                'tax_total'     => $request->tax_total,
                'total'         => $request->total_amount,
            ];
            // echo "<pre>";
            // print_r($dynamicData);exit;
            $subject  = $email_templates->subject;
            $template = $email_templates->template;

            foreach ($dynamicData as $key => $value) {
                $subject  = str_replace("[$key]", $value, $subject);
                $template = str_replace("[$key]", $value, $template);
            }

            // Prepare the email data
            $emailData = [
                'subject' => $subject,
                'message' => $template,
            ];
            // print_r($employeeDetail->email);exit;

            try {
                if (! empty($employeeDetail->email)) {
                    // Notification::route('mail', $employeeDetail->email)
                    //     ->notify(new SendInvoiceByContractor($emailData));
                    Mail::to($employeeDetail->email)->send(new DynamicEmail($emailData));

                    $employee = User::find($employeeDetail->id); // only for notify
                    if ($employee) {
                        $employee->notify(new SendInvoiceByContractor([
                            'bill_id' => $billId,
                            'bill_no' => $dynamicData['bill_no'],
                            'total'   => $dynamicData['total'],
                            'title'   => 'New bill creat',
                            'message' => 'New bill created by ' . $dynamicData['vendor_name'] .
                            ' on ' . $dynamicData['bill_date'],
                        ]));
                    }
                }

                // Send email to Vendor if email exists
                // if (! empty($vendorDetail->email)) {
                //     // Notification::route('mail', $vendorDetail->email)
                //     //     ->notify(new SendInvoiceByContractor($emailData));
                //                              Mail::to($vendorDetail->email)->send(new DynamicEmail($emailData));
                // }

            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
            }

        }
        return Reply::dataOnly(['status' => 'success']);
    }

    //   public function topSheet($id)
    // {
    //     $billId          = $id;
    //     $this->pageTitle = __('app.createTopSheet');

    //     $this->bill = DB::table('purchase_bills as pb')
    //         ->join('purchase_vendors as pv', 'pv.id', '=', 'pb.purchase_vendor_id')
    //         ->join('purchase_orders  as po', 'po.id', '=', 'pb.purchase_order_id')
    //         ->join('projects  as p', 'p.id', '=', 'po.project_id')
    //         ->join('tasks  as t', 't.project_id', '=', 'p.id')
    //         ->where('pb.id', $billId)
    //         ->select('pb.*', 'pv.*', 'po.*', 'p.*', 't.heading as sub_project')
    //         ->first();

    //     $this->sum_PreviousBillTotale = DB::table('purchase_bills')
    //         ->where('purchase_order_id', 1)
    //         ->whereIn('status', ['open', 'paid', 'partially_paid'])
    //         ->sum('total');

    //     if (request()->ajax()) {
    //         $html = view('purchase::bills.ajax.topSheet', $this->data)->render();

    //         return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
    //     }

    //     $this->view = 'purchase::bills.ajax.topSheet';

    //     return view('purchase::bills.createTopSheet', $this->data);
    // }

    //   public function topSheet($id)
    // {

    //     $this->pageTitle = __('app.createTopSheet');
    //     $billId          = $id;

    //     $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
    //     if (! $this->bill_detail) {
    //         abort(404, 'Bill not found');
    //     }
    //     $vendor_id = $this->bill_detail->purchase_vendor_id;
    //     $po_id     = $this->bill_detail->purchase_order_id;

    //     $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
    //     $project_id            = $this->po_details->project_id;
    //     $sub_projectID         = $this->po_details->subproject_id;
    //     $this->project_details = DB::table('projects')->where('id', $project_id)->first();
    //     $this->sub_project     = DB::table('tasks')->where('id', $sub_projectID)->first();
    //     $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();
    //     $this->purchase_items  = DB::table('purchase_items')
    //         ->where('purchase_order_id', $po_id)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
    //         ->first();

    //     $this->woBaseValue   = $this->purchase_items->total_amount;
    //     $this->WOtotalGstAmt = $this->purchase_items->total_gst;

    //     $this->woGrossValue = $this->woBaseValue + $this->WOtotalGstAmt;
    //     $this->WOtaxPercent = $this->purchase_items->tax_percent;

    //     $totals = DB::table('purchase_bills')
    //         ->where('purchase_order_id', $po_id)->where('id', '!=', $billId)
    //         ->selectRaw('SUM(sub_total) as sub_total, SUM(tax_total) as tax_total, SUM(total) as total')
    //         ->first();
    //     $CurrentretentionTotal = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->sum('retention_cost');
    //     $this->retention_amount = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->value('retention_amount');

    //     $this->subTotal   = $totals->sub_total;
    //     $this->taxTotal   = $totals->tax_total;
    //     $this->grandTotal = $totals->total;

    //     $this->releaseHoldAmt = 0;

    //     $this->Currentsub_total      = $this->bill_detail->sub_total;
    //     $this->Currenttax_total      = $this->bill_detail->tax_total;
    //     $this->Currentretention_cost = $CurrentretentionTotal;
    //     $this->Currenttotal          = $this->bill_detail->total;

    //     $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
    //     $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
    //     $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

    //     $this->AdvanceRecovery = $this->bill_detail->AdvanceRecovery;
    //     $this->HoldBillAmt     = $this->bill_detail->HoldBillAmt;

    //     $this->totalAmtrecoveandretainion = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;

    //     $this->Steel            = $this->bill_detail->Steel;
    //     $this->Cement           = $this->bill_detail->Cement;
    //     $this->Electricity      = $this->bill_detail->Electricity;
    //     $this->OtherDebits      = $this->bill_detail->OtherDebits;
    //     $this->TotalDebitAmount = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;

    //     $this->NetPreviousTotal = $this->grandTotal;

    //     $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;

    //     $this->NetTotalAmount = $this->NetPreviousTotal + $this->NetPayableAmount;

    //             // $approval_bill_details = DB::table('approval_bill_details as abd')
    //             //     ->join('priority_role as pr', 'pr.user_id', '=', 'abd.user_id')
    //             //     ->join('users as u', 'u.id', '=', 'abd.user_id')
    //             //     ->where('abd.bill_id', $billId)
    //             //     ->select('abd.*', 'pr.priority_id', 'u.name')
    //             //     ->groupBy('abd.user_id')
    //             //     ->orderBy('pr.priority_id', 'asc')
    //             //     ->get();

    //         // $approval_bill_details = DB::table('priority_role as pr')
    //         //         ->join('users as u', 'u.id', '=', 'pr.user_id')
    //         //         ->where('pr.project_id', $project_id)
    //         //         ->select('pr.*', 'u.name')
    //         //         ->orderBy('pr.priority_id', 'asc')
    //         //         ->get();

    //             // isset($approval_bill_details[0]) ? $this->preparedBy   = $approval_bill_details[0]->name : $this->preparedBy   = '';
    //             // isset($approval_bill_details[1]) ? $this->checkby   = $approval_bill_details[1]->name : $this->checkby   = '';
    //             // isset($approval_bill_details[2]) ? $this->verifyby  = $approval_bill_details[2]->name : $this->verifyby  = '';
    //             // isset($approval_bill_details[3]) ? $this->approveby = $approval_bill_details[3]->name : $this->approveby = '';

    //                 $preparedBy = DB::table('approval_bills_by as ab')
    //             ->join('purchase_bills as pb', 'pb.id', '=', 'ab.bill_id')
    //             ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
    //             ->join('priority_role as pr', 'pr.project_id', '=', 'po.project_id')
    //             ->join('users as u', 'u.id', '=', 'pr.user_id')
    //             ->where('ab.bill_id', $billId)
    //             ->where('pr.priority_id', 1)
    //             ->select('u.name')
    //             ->first(); // since you're expecting a single record

    //         $this->preparedBy = $preparedBy ? $preparedBy->name : '';

    //         $approvalBillDetails = DB::table('approval_bill_details as abd')
    //             ->join('users as u', 'u.id', '=', 'abd.user_id')
    //             ->whereIn('abd.id', function ($query) use ($billId) {
    //                 $query->selectRaw('MIN(id)')
    //                     ->from('approval_bill_details')
    //                     ->where('bill_id', $billId)
    //                     ->where('approval_status', 2)
    //                     ->groupBy('user_id');
    //             })
    //             ->select('abd.*', 'u.name')
    //             ->limit(3)
    //             ->get();

    //         // Assign values (accessing 'name' from each result object)
    //         $this->checkby   = isset($approvalBillDetails[0]) ? $approvalBillDetails[0]->name : '--';
    //         $this->verifyby  = isset($approvalBillDetails[1]) ? $approvalBillDetails[1]->name : '--';
    //         $this->approveby = isset($approvalBillDetails[2]) ? $approvalBillDetails[2]->name : '--';

    //     // echo("<pre>");
    //     //     print_r($this->company->company_name);exit;
    //     if (request()->ajax()) {
    //         $html = view('purchase::bills.ajax.topSheet', $this->data)->render();

    //         return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
    //     }

    //     $this->view = 'purchase::bills.ajax.topSheet';

    //     return view('purchase::bills.createTopSheet', $this->data);
    // }

    // public function topSheet($id)
    // {

    //     $this->pageTitle = __('app.createTopSheet');
    //     $billId          = $id;

    //     $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
    //     if (! $this->bill_detail) {
    //         abort(404, 'Bill not found');
    //     }
    //     $vendor_id = $this->bill_detail->purchase_vendor_id;
    //     $po_id     = $this->bill_detail->purchase_order_id;

    //     $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
    //     $project_id            = $this->po_details->project_id;
    //     $sub_projectID         = $this->po_details->subproject_id;
    //     $this->project_details = DB::table('projects')->where('id', $project_id)->first();
    //     $this->sub_project     = DB::table('tasks')->where('id', $sub_projectID)->first();
    //     $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();
    //     $this->purchase_items  = DB::table('purchase_items')
    //         ->where('purchase_order_id', $po_id)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
    //         ->first();

    //     $this->woBaseValue   = $this->purchase_items->total_amount;
    //     $this->WOtotalGstAmt = $this->purchase_items->total_gst;

    //     $this->woGrossValue = $this->woBaseValue + $this->WOtotalGstAmt;
    //     $this->WOtaxPercent = $this->purchase_items->tax_percent;

    //     $po_release_hold_amt = DB::table('release_hold_amt')
    //         ->where('po_id', $po_id)
    //         ->where('release_bill_id', '!=', $billId)
    //         ->get();

    //     if ($po_release_hold_amt->isNotEmpty()) {
    //         $this->po_releaseHoldAmt = array_sum($po_release_hold_amt->pluck('hold_amt')->toArray());
    //     } else {
    //         $this->po_releaseHoldAmt = 0;
    //     }
    //     $release_hold_amt = DB::table('release_hold_amt')
    //         ->where('release_bill_id', $billId)
    //         ->get();

    //     if ($release_hold_amt->isNotEmpty()) {
    //         $this->releaseHoldAmt = array_sum($release_hold_amt->pluck('hold_amt')->toArray());
    //     } else {
    //         $this->releaseHoldAmt = 0;
    //     }

    //     $curr_bill = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount,SUM(retention_cost) as total_retention_cost')
    //         ->first();

    //     $previousAmount = $this->getPriviousBillAmount($po_id, $billId);

    //     // print_r($previousAmount);  exit;

    //     // $totals = DB::table('purchase_bill_items as pi')
    //     //     ->join('purchase_bills as b', 'b.id', '=', 'pi.purchase_bills_id')
    //     //     ->where('b.purchase_order_id', $po_id)
    //     //     ->where('b.id', '!=', $billId)
    //     //             ->selectRaw('
    //     //         SUM(pi.amount) AS total_amount,
    //     //         SUM(pi.tax_amount) AS total_tax_amount,
    //     //         SUM(pi.retention_cost) AS total_retention_cost
    //     //     ')
    //     //     ->first();

    //     $this->subTotal        = $previousAmount['totalCertifiedAmount'];
    //     $this->taxTotal        = $previousAmount['totalTaxAmount'];
    //     $this->totalNetPayable = $previousAmount['totalNetPayable'];

    //     $this->com_po_releaseHoldAmt = $this->releaseHoldAmt + $this->po_releaseHoldAmt;
    //     $this->grandTotal            = $this->totalNetPayable + $this->po_releaseHoldAmt;

    //     $this->Currentsub_total      = $curr_bill->total_amount;
    //     $this->Currenttax_total      = $curr_bill->total_tax_amount;
    //     $this->Currentretention_cost = $curr_bill->total_retention_cost;
    //     $this->Currenttotal          = $this->Currentsub_total + $this->Currenttax_total + $this->releaseHoldAmt;

    //     $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
    //     $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
    //     $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

    //     $this->AdvanceRecovery = $this->bill_detail->AdvanceRecovery;
    //     $this->HoldBillAmt     = $this->bill_detail->HoldBillAmt;

    //     $this->totalAmtrecoveandretainion = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;

    //     $this->Steel       = $this->bill_detail->Steel;
    //     $this->Cement      = $this->bill_detail->Cement;
    //     $this->Electricity = $this->bill_detail->Electricity;

    //     $instant_debit = DB::table('instant_debit')
    //         ->where('bill_id', $billId)
    //         ->where('bill_status', 2)
    //         ->sum('instantdebitamount') ?? 0;
    //     $this->OtherDebits = $this->bill_detail->SafetyDebit + $this->bill_detail->QualityDebit + $this->bill_detail->Housekeeping + $this->bill_detail->PoorPerformance + $this->bill_detail->OtherDebits + ($instant_debit);

    //     $this->TotalDebitAmount = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;

    //     $this->NetPreviousTotal = $this->grandTotal;

    //     $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;

    //     $this->NetTotalAmount = $this->NetPreviousTotal + $this->NetPayableAmount;

    //     $this->preparedBy = DB::table('abstract_bypass as ab')
    //         ->join('users as u', 'u.id', '=', 'ab.user_id')
    //         ->where('ab.bill_id', $billId)
    //         ->select('u.name', 'u.signature')
    //         ->first();

    //     $priorityRoles = DB::table('priority_role')
    //         ->where('project_id', $project_id)
    //         ->get();

    //     // $this->prUsers = DB::table('priority_role as pr')
    //     //     ->join('users as u', 'u.id', '=', 'pr.user_id')
    //     //     ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
    //     //     ->join('designations as d', 'd.id', '=', 'e.designation_id')
    //     //     ->where('pr.project_id', $project_id)
    //     //     ->orderBy('pr.priority_id', 'asc')
    //     //     ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
    //     //     ->get();
    //     $this->prUsers = DB::table(DB::raw("(
    //     SELECT * FROM priority_role
    //     WHERE project_id = $project_id
    //     ORDER BY priority_id DESC
    //     LIMIT 5
    // ) as pr"))
    // ->join('users as u', 'u.id', '=', 'pr.user_id')
    // ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
    // ->join('designations as d', 'd.id', '=', 'e.designation_id')
    // ->orderBy('pr.priority_id', 'asc') // Re-order after limiting
    // ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
    // ->get();

    //     // $approvedUsers = [];

    //     // foreach ($priorityRoles as $key => $value) {
    //     //     $exists = DB::table('approval_bill_details')
    //     //         ->where('bill_id', $billId)
    //     //         ->where('user_id', $value->user_id)
    //     //         ->where(function ($query) {
    //     //             $query->where('approval_status', 2)
    //     //                   ->orWhere('bypass', 'bypass');
    //     //         })
    //     //         ->exists();

    //     //     if ($exists) {
    //     //         $user = DB::table('users as u')
    //     //             ->join('approval_bill_details as ab', 'u.id', '=', 'ab.user_id')
    //     //             ->where('u.id', $value->user_id)
    //     //             ->where('ab.bill_id', $billId)
    //     //             ->select('u.name', 'u.signature', 'ab.*')
    //     //             ->first();

    //     //         if ($user) {
    //     //             $approvedUsers[] = $user;
    //     //         }
    //     //     }
    //     // }

    //     // // Assigning to properties
    //     // $this->checkby   = $approvedUsers[0] ?? '--';
    //     // $this->verifyby  = $approvedUsers[1] ?? '--';
    //     // $this->approveby = $approvedUsers[2] ?? '--';

    //     if (request()->ajax()) {
    //         $html = view('purchase::bills.ajax.topSheet', $this->data)->render();

    //         return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
    //     }

    //     $this->view = 'purchase::bills.ajax.topSheet';

    //     return view('purchase::bills.createTopSheet', $this->data);
    // }


    public function topSheet($id)
    {
        $this->pageTitle = __('app.createTopSheet');
        $billId          = $id;

        // ========= Fetch Core Data =========
        $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
        if (! $this->bill_detail) {
            abort(404, 'Bill not found');
        }

        $po_id     = $this->bill_detail->purchase_order_id;
        $vendor_id = $this->bill_detail->purchase_vendor_id;

        $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
        $project_id            = $this->po_details->project_id;
        $this->project_details = DB::table('projects')->where('id', $project_id)->first();
        $this->sub_project     = DB::table('tasks')->where('id', $this->po_details->subproject_id)->first();
        $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();

        $Previous_Bill_records = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->where('pb.purchase_order_id', $po_id)
            ->where('pb.id', '<', $billId)
            ->select('pb.*')
            ->get();

        // ========= Purchase Items =========
        $this->purchase_items = DB::table('purchase_items')
            ->where('purchase_order_id', $po_id)
            ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
            ->first();

        $this->woBaseValue   = $this->purchase_items->total_amount;
        $this->WOtotalGstAmt = $this->purchase_items->total_gst;
        $this->woGrossValue  = $this->woBaseValue + $this->WOtotalGstAmt;
        $this->WOtaxPercent  = $this->purchase_items->tax_percent;

        // ========= Hold Amounts =========
        $this->po_releaseHoldAmt = DB::table('release_hold_amt')
            ->where('po_id', $po_id)
            ->where('release_bill_id', '!=', $billId)
            ->sum('hold_amt') ?? 0;

        $this->releaseHoldAmt = DB::table('release_hold_amt')
            ->where('release_bill_id', $billId)
            ->sum('hold_amt') ?? 0;

        // ========= Current Bill =========
        $curr_bill = DB::table('purchase_bill_items')
            ->where('purchase_bills_id', $billId)
            ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount, SUM(retention_cost) as total_retention_cost')
            ->first();

        $previousAmount = $this->getPriviousBillAmount($po_id, $billId);

        $this->subTotal        = $previousAmount['totalCertifiedAmount'];
        $this->taxTotal        = $previousAmount['totalTaxAmount'];
        $this->totalNetPayable = $previousAmount['totalNetPayable'];

        $this->com_po_releaseHoldAmt = $this->releaseHoldAmt + $this->po_releaseHoldAmt;
        $this->grandTotal            = $this->totalNetPayable + $this->po_releaseHoldAmt;

        $this->Currentsub_total      = $curr_bill->total_amount;
        $this->Currenttax_total      = $curr_bill->total_tax_amount;
        $this->Currentretention_cost = $curr_bill->total_retention_cost;

        $this->previous_retentioncost = DB::table('purchase_bill_items as pbi')
            ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
            ->where('pb.purchase_order_id', $po_id)
            ->where('pb.id', '<', $billId)
            ->sum('pbi.retention_cost');

        $this->total_retention_cost = $this->previous_retentioncost + $this->Currentretention_cost;
        $this->Currenttotal         = $this->Currentsub_total + $this->Currenttax_total + $this->releaseHoldAmt;

        $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
        $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
        $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

        // ========= Recoveries =========
        $this->previous_AdvanceRecovery = $Previous_Bill_records->sum('AdvanceRecovery');
        $this->AdvanceRecovery          = $this->bill_detail->AdvanceRecovery;
        $this->total_AdvanceRecovery    = $this->previous_AdvanceRecovery + $this->AdvanceRecovery;

        $totalReleaseHoldAmtfromBillId = DB::table('release_hold_amt')
            ->where('po_id', $po_id)
            ->where('bill_id', '<', $billId)
            ->whereNotNull('release_bill_id')
            ->sum('hold_amt');

        $this->previous_HoldBillAmt = $Previous_Bill_records->sum('HoldBillAmt') - $totalReleaseHoldAmtfromBillId;
        $this->HoldBillAmt          = $this->bill_detail->HoldBillAmt;
        $this->total_HoldBillAmt    = $this->previous_HoldBillAmt + $this->HoldBillAmt;

        $this->previous_totalAmtrecoveandretainion = $this->previous_AdvanceRecovery + $this->previous_retentioncost + $this->previous_HoldBillAmt;
        $this->totalAmtrecoveandretainion          = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;
        $this->post_totalAmtrecoveandretainion     = $this->previous_totalAmtrecoveandretainion + $this->totalAmtrecoveandretainion;

        // ========= Material & Utilities =========
        $this->previous_Steel       = $Previous_Bill_records->sum('Steel');
        $this->previous_Cement      = $Previous_Bill_records->sum('Cement');
        $this->previous_Electricity = $Previous_Bill_records->sum('Electricity');

        $this->Steel       = $this->bill_detail->Steel;
        $this->Cement      = $this->bill_detail->Cement;
        $this->Electricity = $this->bill_detail->Electricity;

        $this->total_steel       = $this->previous_Steel + $this->Steel;
        $this->total_cement      = $this->previous_Cement + $this->Cement;
        $this->total_electricity = $this->previous_Electricity + $this->Electricity;

        // ========= Debits =========
        $instant_debit = DB::table('instant_debit')
            ->where('bill_id', $billId)
            ->where('bill_status', 2)
            ->sum('instantdebitamount') ?? 0;

        $previous_instant_debit = DB::table('instant_debit')
            ->where('po_id', $po_id)
            ->where('bill_status', 2)
            ->where('bill_id', '<', $billId)
            ->sum('instantdebitamount') ?? 0;

        $this->previous_otherDebits = $Previous_Bill_records->sum('OtherDebits')
         + $Previous_Bill_records->sum('SafetyDebit')
         + $Previous_Bill_records->sum('QualityDebit')
         + $Previous_Bill_records->sum('Housekeeping')
         + $Previous_Bill_records->sum('PoorPerformance')
             + $previous_instant_debit;

        $this->OtherDebits = $this->bill_detail->SafetyDebit + $this->bill_detail->QualityDebit
         + $this->bill_detail->Housekeeping + $this->bill_detail->PoorPerformance
         + $this->bill_detail->OtherDebits + $instant_debit;

        $this->total_otherDebits = $this->previous_otherDebits + $this->OtherDebits;

        $this->previous_TotalDebitAmount = $this->previous_Steel + $this->previous_Cement + $this->previous_Electricity + $this->previous_otherDebits;
        $this->TotalDebitAmount          = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;
        $this->post_TotalDebitAmount     = $this->previous_TotalDebitAmount + $this->TotalDebitAmount;

        // ========= Final Net Totals =========
        $this->NetPreviousTotal = $this->grandTotal - $this->previous_TotalDebitAmount - $this->previous_totalAmtrecoveandretainion;
        $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;
        $this->NetTotalAmount   = $this->NetPreviousTotal + $this->NetPayableAmount;

        // ========= Prepared By =========
        $this->preparedBy = DB::table('abstract_bypass as ab')
            ->join('users as u', 'u.id', '=', 'ab.user_id')
            ->where('ab.bill_id', $billId)
            ->select('u.name', 'u.signature')
            ->first();

        // ========= Priority Users =========
        $this->prUsers = DB::table(DB::raw("(SELECT * FROM priority_role WHERE project_id =  $project_id ORDER BY priority_id DESC LIMIT 5) as pr"))
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
            ->join('designations as d', 'd.id', '=', 'e.designation_id')
            ->orderBy('pr.priority_id', 'asc')
            ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
            ->get();

        // ========= Response =========
        if (request()->ajax()) {
            $html = view('purchase::bills.ajax.topSheet', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::bills.ajax.topSheet';
        return view('purchase::bills.createTopSheet', $this->data);
    }

    private function getPriviousBillAmount($po_id, $billId)
    {
        $total_po_bills = DB::table('purchase_bills')
            ->where('purchase_order_id', $po_id)
            ->where('id', '!=', $billId)
            ->get();
        if ($total_po_bills->isEmpty()) {
            return [
                'totalAmount'          => 0,
                'totalTaxAmount'       => 0,
                'totalRetentionCost'   => 0,
                'totalReleaseHoldAmt'  => 0,
                'totalAdvanceRecovery' => 0,
                'totalHoldBillAmt'     => 0,
                'totalRecoveries'      => 0,
                'totalInstantDebit'    => 0,
                'totalOtherDebits'     => 0,
                'totalMaterialDebits'  => 0,
                'totalFinalDebit'      => 0,
                'totalNetPayable'      => 0,
                'totalCertifiedAmount' => 0,
            ];
        }
        $totalAmount          = 0;
        $totalTaxAmount       = 0;
        $totalRetentionCost   = 0;
        $totalReleaseHoldAmt  = 0;
        $totalAdvanceRecovery = 0;
        $totalHoldBillAmt     = 0;
        $totalRecoveries      = 0;
        $totalInstantDebit    = 0;
        $totalOtherDebits     = 0;
        $totalMaterialDebits  = 0;
        $totalFinalDebit      = 0;
        $totalNetPayable      = 0;

        foreach ($total_po_bills as $bill) {
            $bill_id = $bill->id;

            // Get bill item sums
            $curr_bill = DB::table('purchase_bill_items')
                ->where('purchase_bills_id', $bill_id)
                ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount, SUM(retention_cost) as total_retention_cost')
                ->first();

            // Released hold amount
            $releaseHoldAmt = DB::table('release_hold_amt')
                ->where('release_bill_id', $bill_id)
                ->sum('hold_amt');

            // Instant debit
            $instant_debit = DB::table('instant_debit')
                ->where('bill_id', $bill_id)
                ->where('bill_status', 2)
                ->sum('instantdebitamount') ?? 0;

            // Other manual debits
            $OtherDebits = collect([
                $bill->SafetyDebit ?? 0,
                $bill->QualityDebit ?? 0,
                $bill->Housekeeping ?? 0,
                $bill->PoorPerformance ?? 0,
                $bill->OtherDebits ?? 0,
                $instant_debit,
            ])->sum();

            $materialDebits =
                ($bill->Steel ?? 0) +
                ($bill->Cement ?? 0) +
                ($bill->Electricity ?? 0);

            // Accumulate totals
            $sub_total        = $curr_bill->total_amount ?? 0;
            $tax_total        = $curr_bill->total_tax_amount ?? 0;
            $retention_cost   = $curr_bill->total_retention_cost ?? 0;
            $advanceRecovery  = $bill->AdvanceRecovery ?? 0;
            $holdBillAmt      = $bill->HoldBillAmt ?? 0;
            $recoveries       = $advanceRecovery + $retention_cost + $holdBillAmt;
            $finalDebitAmount = $materialDebits + $OtherDebits;

            $netPayable           = ($sub_total + $tax_total + $releaseHoldAmt) - $recoveries - $finalDebitAmount;
            $totalCertifiedAmount = ($sub_total + $releaseHoldAmt) - $recoveries - $finalDebitAmount;

            // Add to grand totals
            $totalAmount += $sub_total;
            $totalTaxAmount += $tax_total;
            $totalRetentionCost += $retention_cost;
            $totalReleaseHoldAmt += $releaseHoldAmt;
            $totalAdvanceRecovery += $advanceRecovery;
            $totalHoldBillAmt += $holdBillAmt;
            $totalRecoveries += $recoveries;
            $totalInstantDebit += $instant_debit;
            $totalOtherDebits += $OtherDebits;
            $totalMaterialDebits += $materialDebits;
            $totalFinalDebit += $finalDebitAmount;
            $totalNetPayable += $netPayable;

            return [
                'totalAmount'          => $totalAmount,
                'totalTaxAmount'       => $totalTaxAmount,
                'totalRetentionCost'   => $totalRetentionCost,
                'totalReleaseHoldAmt'  => $totalReleaseHoldAmt,
                'totalAdvanceRecovery' => $totalAdvanceRecovery,
                'totalHoldBillAmt'     => $totalHoldBillAmt,
                'totalRecoveries'      => $totalRecoveries,
                'totalInstantDebit'    => $totalInstantDebit,
                'totalOtherDebits'     => $totalOtherDebits,
                'totalMaterialDebits'  => $totalMaterialDebits,
                'totalFinalDebit'      => $totalFinalDebit,
                'totalNetPayable'      => $totalNetPayable,
                'totalCertifiedAmount' => $totalCertifiedAmount,
            ];
        }

    }

    public function billApproval(PurchaseBillApprovalDataTable $dataTable)
    {
        $this->vendors = PurchaseVendor::with('currency')->get();

        $this->pageTitle = 'purchase::app.menu.approveBill';
        $this->groups = DB::table('client_details')
            ->select('id', 'company_name')
            ->get();
        $this->projects = DB::table('projects')
            ->select('id', 'project_name')
            ->get();

        return $dataTable->render('purchase::bills.billApproval', $this->data);
    }
    
    public function billDelay(PurchaseBillDelayDataTable $dataTable)
        {
    $this->vendors = PurchaseVendor::with('currency')->get();

            $this->pageTitle = 'purchase::app.menu.delayBill';
            $this->groups = DB::table('client_details')
                ->select('id', 'company_name')
                ->get();
            $this->projects = DB::table('projects')
                ->select('id', 'project_name')
                ->get();
            return $dataTable->render('purchase::bills.billDelay', $this->data);
        }

    public function invoice_details($id)
    {

        $userId       = session('user')->id;
        $role_id      = DB::table('role_user')->where('user_id', $userId)->first();
        $bill_item_id = $id;

        $b_deatils = $data = DB::table('approval_bill_details as abd')
            ->join('purchase_bills as pb', 'pb.id', '=', 'abd.bill_id')
            ->join('users as u', 'u.id', '=', 'abd.user_id')
            ->join('approval_status  as aps', 'aps.id', '=', 'abd.approval_status')
            ->where('abd.bill_id', $bill_item_id)
            ->select('abd.*', 'u.name', 'u.image', 'aps.status_name')
            ->get();

        $purchase_billsdata = DB::table('purchase_bills as b')
            ->join('purchase_orders as po', 'po.id', '=', 'b.purchase_order_id')
            ->join('projects as p', 'p.id', '=', 'po.project_id')
            ->join('tasks as t', 't.id', '=', 'po.subproject_id')
            ->select('b.id', 'p.project_name', 't.heading', 't.task_short_code')
            ->where('b.id', $bill_item_id)
            ->first();

        $this->data['b_deatils']          = $b_deatils;
        $this->data['purchase_billsdata'] = $purchase_billsdata;

        return view('purchase::bills.approval_bill_details', $this->data);
    }

    public function generateNewPdf($id)
    {
        $pdfOption = $this->domPdfObjectForDownloadx($id); // Using the class properties now
        $pdf       = $pdfOption['pdf'];
        $filename  = $pdfOption['fileName'];

        // Return the PDF either for streaming or downloading
        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    //  public function domPdfObjectForDownloadx($id)
    // {
    //     // Create the PDF using DomPDF
    //     $pdf = app('dompdf.wrapper');
    //     $pdf->setOption('enable_php', false); // Set to false for security reasons
    //     $pdf->setOption('isHtml5ParserEnabled', true);
    //     $pdf->setOption('isRemoteEnabled', true);

    //     $billId          = $id;

    //     $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
    //     if (! $this->bill_detail) {
    //         abort(404, 'Bill not found');
    //     }
    //     $vendor_id = $this->bill_detail->purchase_vendor_id;
    //     $po_id     = $this->bill_detail->purchase_order_id;

    //     $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
    //     $project_id            = $this->po_details->project_id;
    //     $sub_projectID         = $this->po_details->subproject_id;
    //     $this->project_details = DB::table('projects')->where('id', $project_id)->first();
    //     $this->sub_project     = DB::table('tasks')->where('id', $sub_projectID)->first();
    //     $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();
    //     $this->purchase_items  = DB::table('purchase_items')
    //         ->where('purchase_order_id', $po_id)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
    //         ->first();

    //     $this->woBaseValue   = $this->purchase_items->total_amount;
    //     $this->WOtotalGstAmt = $this->purchase_items->total_gst;

    //     $this->woGrossValue = $this->woBaseValue + $this->WOtotalGstAmt;
    //     $this->WOtaxPercent = $this->purchase_items->tax_percent;

    //     $totals = DB::table('purchase_bills')
    //         ->where('purchase_order_id', $po_id)->where('id', '!=', $billId)
    //         ->selectRaw('SUM(sub_total) as sub_total, SUM(tax_total) as tax_total, SUM(total) as total')
    //         ->first();
    //     $CurrentretentionTotal = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->sum('retention_cost');
    //     $this->retention_amount = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->value('retention_amount');

    //     $this->subTotal   = $totals->sub_total;
    //     $this->taxTotal   = $totals->tax_total;
    //     $this->grandTotal = $totals->total;

    //     $this->releaseHoldAmt = 0;

    //     $this->Currentsub_total      = $this->bill_detail->sub_total;
    //     $this->Currenttax_total      = $this->bill_detail->tax_total;
    //     $this->Currentretention_cost = $CurrentretentionTotal;
    //     $this->Currenttotal          = $this->bill_detail->total;

    //     $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
    //     $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
    //     $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

    //     $this->AdvanceRecovery = $this->bill_detail->AdvanceRecovery;
    //     $this->HoldBillAmt     = $this->bill_detail->HoldBillAmt;

    //     $this->totalAmtrecoveandretainion = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;

    //     $this->Steel            = $this->bill_detail->Steel;
    //     $this->Cement           = $this->bill_detail->Cement;
    //     $this->Electricity      = $this->bill_detail->Electricity;
    //     $this->OtherDebits      = $this->bill_detail->OtherDebits;
    //     $this->TotalDebitAmount = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;

    //     $this->NetPreviousTotal = $this->grandTotal;

    //     $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;

    //     $this->NetTotalAmount = $this->NetPreviousTotal + $this->NetPayableAmount;

    //             // $approval_bill_details = DB::table('approval_bill_details as abd')
    //             //     ->join('priority_role as pr', 'pr.user_id', '=', 'abd.user_id')
    //             //     ->join('users as u', 'u.id', '=', 'abd.user_id')
    //             //     ->where('abd.bill_id', $billId)
    //             //     ->select('abd.*', 'pr.priority_id', 'u.name')
    //             //     ->groupBy('abd.user_id')
    //             //     ->orderBy('pr.priority_id', 'asc')
    //             //     ->get();

    //         // $approval_bill_details = DB::table('priority_role as pr')
    //         //         ->join('users as u', 'u.id', '=', 'pr.user_id')
    //         //         ->where('pr.project_id', $project_id)
    //         //         ->select('pr.*', 'u.name')
    //         //         ->orderBy('pr.priority_id', 'asc')
    //         //         ->get();

    //             // isset($approval_bill_details[0]) ? $this->preparedBy   = $approval_bill_details[0]->name : $this->preparedBy   = '';
    //             // isset($approval_bill_details[1]) ? $this->checkby   = $approval_bill_details[1]->name : $this->checkby   = '';
    //             // isset($approval_bill_details[2]) ? $this->verifyby  = $approval_bill_details[2]->name : $this->verifyby  = '';
    //             // isset($approval_bill_details[3]) ? $this->approveby = $approval_bill_details[3]->name : $this->approveby = '';

    //                 $preparedBy = DB::table('approval_bills_by as ab')
    //             ->join('purchase_bills as pb', 'pb.id', '=', 'ab.bill_id')
    //             ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
    //             ->join('priority_role as pr', 'pr.project_id', '=', 'po.project_id')
    //             ->join('users as u', 'u.id', '=', 'pr.user_id')
    //             ->where('ab.bill_id', $billId)
    //             ->where('pr.priority_id', 1)
    //             ->select('u.name')
    //             ->first(); // since you're expecting a single record

    //         $this->preparedBy = $preparedBy ? $preparedBy->name : '';

    //         $approvalBillDetails = DB::table('approval_bill_details as abd')
    //             ->join('users as u', 'u.id', '=', 'abd.user_id')
    //             ->whereIn('abd.id', function ($query) use ($billId) {
    //                 $query->selectRaw('MIN(id)')
    //                     ->from('approval_bill_details')
    //                     ->where('bill_id', $billId)
    //                     ->where('approval_status', 2)
    //                     ->groupBy('user_id');
    //             })
    //             ->select('abd.*', 'u.name')
    //             ->limit(3)
    //             ->get();

    //         // Assign values (accessing 'name' from each result object)
    //         $this->checkby   = isset($approvalBillDetails[0]) ? $approvalBillDetails[0]->name : '--';
    //         $this->verifyby  = isset($approvalBillDetails[1]) ? $approvalBillDetails[1]->name : '--';
    //         $this->approveby = isset($approvalBillDetails[2]) ? $approvalBillDetails[2]->name : '--';

    //     // echo "<pre>";
    //     // print_r($this->company->company_name);exit;
    //     // Load the view and pass the data to the PDF
    //       $pdf->loadView('purchase::bills.pdf.topSheet', [
    //         'company_name' => $this->company->company_name,
    //         'company_gstin' => $this->company->gstin,
    //         'billId' => $billId,
    //         'WOtotalGstAmt'=> $this->WOtotalGstAmt,
    //         'Currentsub_total'=> $this->Currentsub_total,
    //         'Cumulativesub_total'=> $this->Cumulativesub_total,
    //         'Currenttax_total'=> $this->Currenttax_total,
    //         'Cumulativetax_total'=> $this->Cumulativetax_total,
    //         'Currentretention_cost'=> $this->Currentretention_cost,
    //         'Currenttotal'=>   $this->Currenttotal,
    //         'Cumulativetotal'=> $this->Cumulativetotal,
    //         'AdvanceRecovery'=> $this->AdvanceRecovery,
    //         'HoldBillAmt'=> $this->HoldBillAmt,
    //         'totalAmtrecoveandretainion'=> $this->totalAmtrecoveandretainion,
    //         'Steel'=>   $this->Steel,
    //         'Cement'=>  $this->Cement,
    //         'Electricity'=> $this->Electricity,
    //         'releaseHoldAmt'=>0,
    //         'OtherDebits'=> $this->OtherDebits,
    //         'TotalDebitAmount'=>   $this->TotalDebitAmount,
    //         'NetPreviousTotal'=> $this->NetPreviousTotal,
    //         'NetPayableAmount'=> $this->NetPayableAmount,
    //         'bill_detail' => $this->bill_detail,
    //         'po_details' => $this->po_details,
    //         'project_details' => $this->project_details,
    //         'vendor_details' => $this->vendor_details,
    //         'sub_project' => $this->sub_project,
    //         'woBaseValue' => $this->woBaseValue,
    //         'woGrossValue' => $this->woGrossValue,
    //         'woTotalGstAmt' => $this->WOtotalGstAmt,
    //         'subTotal' => $this->subTotal,
    //         'taxTotal' => $this->taxTotal,
    //         'grandTotal' => $this->grandTotal,
    //         'preparedBy' => $this->preparedBy,
    //         'checkby' => $this->checkby,
    //         'verifyby' => $this->verifyby,
    //         'approveby' => $this->approveby,
    //         'NetPayableAmount' => $this->NetPayableAmount,
    //         'NetTotalAmount' => $this->NetTotalAmount,
    //     ]);

    //     // Set the filename dynamically using the bill ID or project name
    //     $filename = 'top_sheet_' . $this->project_details->project_name . '_' . $id;

    //     return [
    //         'pdf'      => $pdf,
    //         'fileName' => $filename,
    //     ];
    // }

    // public function domPdfObjectForDownloadx($id)
    // {
    //     // Create the PDF using DomPDF
    //     $pdf = app('dompdf.wrapper');
    //     $pdf->setOption('enable_php', false); // Set to false for security reasons
    //     $pdf->setOption('isHtml5ParserEnabled', true);
    //     $pdf->setOption('isRemoteEnabled', true);

    //     $billId = $id;

    //     $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
    //     if (! $this->bill_detail) {
    //         abort(404, 'Bill not found');
    //     }
    //     $vendor_id = $this->bill_detail->purchase_vendor_id;
    //     $po_id     = $this->bill_detail->purchase_order_id;

    //     $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
    //     $project_id            = $this->po_details->project_id;
    //     $sub_projectID         = $this->po_details->subproject_id;
    //     $this->project_details = DB::table('projects')->where('id', $project_id)->first();
    //     $this->sub_project     = DB::table('tasks')->where('id', $sub_projectID)->first();
    //     $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();
    //     $this->purchase_items  = DB::table('purchase_items')
    //         ->where('purchase_order_id', $po_id)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
    //         ->first();

    //     $this->woBaseValue   = $this->purchase_items->total_amount;
    //     $this->WOtotalGstAmt = $this->purchase_items->total_gst;

    //     $this->woGrossValue = $this->woBaseValue + $this->WOtotalGstAmt;
    //     $this->WOtaxPercent = $this->purchase_items->tax_percent;

    //     $po_release_hold_amt = DB::table('release_hold_amt')
    //         ->where('po_id', $po_id)
    //         ->where('release_bill_id', '!=', $billId)
    //         ->get();

    //     if ($po_release_hold_amt->isNotEmpty()) {
    //         $this->po_releaseHoldAmt = array_sum($po_release_hold_amt->pluck('hold_amt')->toArray());
    //     } else {
    //         $this->po_releaseHoldAmt = 0;
    //     }
    //     $release_hold_amt = DB::table('release_hold_amt')
    //         ->where('release_bill_id', $billId)
    //         ->get();

    //     if ($release_hold_amt->isNotEmpty()) {
    //         $this->releaseHoldAmt = array_sum($release_hold_amt->pluck('hold_amt')->toArray());
    //     } else {
    //         $this->releaseHoldAmt = 0;
    //     }
    //     $curr_bill = DB::table('purchase_bill_items')
    //         ->where('purchase_bills_id', $billId)
    //         ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount,SUM(retention_cost) as total_retention_cost')
    //         ->first();

    //     // $totals = DB::table('purchase_bill_items as pi')
    //     //     ->join('purchase_bills as b', 'b.id', '=', 'pi.purchase_bills_id')
    //     //     ->where('b.purchase_order_id', 1)
    //     //     ->where('b.id', '!=', 15)
    //     //     ->selectRaw('
    //     //     SUM(pi.amount) AS total_amount,
    //     //     SUM(pi.tax_amount) AS total_tax_amount,
    //     //     SUM(pi.retention_cost) AS total_retention_cost
    //     // ')
    //     //     ->first();

    //     $previousAmount = $this->getPriviousBillAmount($po_id, $billId);

    //     // print_r($previousAmount);  exit;

    //     // $totals = DB::table('purchase_bill_items as pi')
    //     //     ->join('purchase_bills as b', 'b.id', '=', 'pi.purchase_bills_id')
    //     //     ->where('b.purchase_order_id', $po_id)
    //     //     ->where('b.id', '!=', $billId)
    //     //             ->selectRaw('
    //     //         SUM(pi.amount) AS total_amount,
    //     //         SUM(pi.tax_amount) AS total_tax_amount,
    //     //         SUM(pi.retention_cost) AS total_retention_cost
    //     //     ')
    //     //     ->first();

    //     $this->subTotal        = $previousAmount['totalCertifiedAmount'];
    //     $this->taxTotal        = $previousAmount['totalTaxAmount'];
    //     $this->totalNetPayable = $previousAmount['totalNetPayable'];

    //     $this->com_po_releaseHoldAmt = $this->releaseHoldAmt + $this->po_releaseHoldAmt;
    //     // $this->subTotal              = $totals->total_amount;
    //     // $this->taxTotal              = $totals->total_tax_amount;
    //     $this->grandTotal = $this->totalNetPayable + $this->po_releaseHoldAmt;

    //     $this->Currentsub_total      = $curr_bill->total_amount;
    //     $this->Currenttax_total      = $curr_bill->total_tax_amount;
    //     $this->Currentretention_cost = $curr_bill->total_retention_cost;
    //     $this->Currenttotal          = $this->Currentsub_total + $this->Currenttax_total + $this->releaseHoldAmt;

    //     $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
    //     $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
    //     $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

    //     $this->AdvanceRecovery = $this->bill_detail->AdvanceRecovery;
    //     $this->HoldBillAmt     = $this->bill_detail->HoldBillAmt;

    //     $this->totalAmtrecoveandretainion = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;

    //     $this->Steel       = $this->bill_detail->Steel;
    //     $this->Cement      = $this->bill_detail->Cement;
    //     $this->Electricity = $this->bill_detail->Electricity;

    //     $instant_debit = DB::table('instant_debit')
    //         ->where('bill_id', $billId)
    //         ->where('bill_status', 2)
    //         ->sum('instantdebitamount') ?? 0;
    //     $this->OtherDebits = $this->bill_detail->SafetyDebit + $this->bill_detail->QualityDebit + $this->bill_detail->Housekeeping + $this->bill_detail->PoorPerformance + $this->bill_detail->OtherDebits + ($instant_debit);

    //     $this->TotalDebitAmount = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;

    //     $this->NetPreviousTotal = $this->grandTotal;

    //     $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;

    //     $this->NetTotalAmount = $this->NetPreviousTotal + $this->NetPayableAmount;

    //     $this->preparedBy = DB::table('abstract_bypass as ab')
    //         ->join('users as u', 'u.id', '=', 'ab.user_id')
    //         ->where('ab.bill_id', $billId)
    //         ->select('u.name', 'u.signature')
    //         ->first();

    //     $priorityRoles = DB::table('priority_role')
    //         ->where('project_id', $project_id)
    //         ->get();
    //     // $this->prUsers = DB::table('priority_role as pr')
    //     //     ->join('users as u', 'u.id', '=', 'pr.user_id')
    //     //     ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
    //     //     ->join('designations as d', 'd.id', '=', 'e.designation_id')
    //     //     ->where('pr.project_id', $project_id)
    //     //     ->orderBy('pr.priority_id', 'asc')
    //     //     ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
    //     //     ->get();
    //      $this->prUsers = DB::table(DB::raw("(
    //     SELECT * FROM priority_role
    //     WHERE project_id = $project_id
    //     ORDER BY priority_id DESC
    //     LIMIT 5
    // ) as pr"))
    // ->join('users as u', 'u.id', '=', 'pr.user_id')
    // ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
    // ->join('designations as d', 'd.id', '=', 'e.designation_id')
    // ->orderBy('pr.priority_id', 'asc') // Re-order after limiting
    // ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
    // ->get();
    //     // $approvedUsers = [];

    //     // foreach ($priorityRoles as $key => $value) {
    //     //     $exists = DB::table('approval_bill_details')
    //     //         ->where('bill_id', $billId)
    //     //         ->where('user_id', $value->user_id)
    //     //         ->where(function ($query) {
    //     //             $query->where('approval_status', 2)
    //     //                   ->orWhere('bypass', 'bypass');
    //     //         })
    //     //         ->exists();

    //     //     if ($exists) {
    //     //         $user = DB::table('users as u')
    //     //             ->join('approval_bill_details as ab', 'u.id', '=', 'ab.user_id')
    //     //             ->where('u.id', $value->user_id)
    //     //             ->where('ab.bill_id', $billId) // ✅ Ensure correct bill
    //     //             ->select('u.name', 'u.signature', 'ab.*')
    //     //             ->first();

    //     //         if ($user) {
    //     //             $approvedUsers[] = $user;
    //     //         }
    //     //     }
    //     // }

    //     // // Assigning to properties
    //     // $this->checkby   = $approvedUsers[0] ?? '--';
    //     // $this->verifyby  = $approvedUsers[1] ?? '--';
    //     // $this->approveby = $approvedUsers[2] ?? '--';
    //     $pdf->loadView('purchase::bills.pdf.topSheet', [
    //         'company_name'               => $this->company->company_name,
    //         'company_gstin'              => $this->company->gstin,
    //         'billId'                     => $billId,
    //         'WOtotalGstAmt'              => $this->WOtotalGstAmt,
    //         'Currentsub_total'           => $this->Currentsub_total,
    //         'Cumulativesub_total'        => $this->Cumulativesub_total,
    //         'Currenttax_total'           => $this->Currenttax_total,
    //         'Cumulativetax_total'        => $this->Cumulativetax_total,
    //         'Currentretention_cost'      => $this->Currentretention_cost,
    //         'Currenttotal'               => $this->Currenttotal,
    //         'po_releaseHoldAmt'          => $this->po_releaseHoldAmt,
    //         'com_po_releaseHoldAmt'      => $this->com_po_releaseHoldAmt,
    //         'Cumulativetotal'            => $this->Cumulativetotal,
    //         'AdvanceRecovery'            => $this->AdvanceRecovery,
    //         'HoldBillAmt'                => $this->HoldBillAmt,
    //         'totalAmtrecoveandretainion' => $this->totalAmtrecoveandretainion,
    //         'Steel'                      => $this->Steel,
    //         'Cement'                     => $this->Cement,
    //         'Electricity'                => $this->Electricity,
    //         'releaseHoldAmt'             => $this->releaseHoldAmt,
    //         'OtherDebits'                => $this->OtherDebits,
    //         'TotalDebitAmount'           => $this->TotalDebitAmount,
    //         'NetPreviousTotal'           => $this->NetPreviousTotal,
    //         'NetPayableAmount'           => $this->NetPayableAmount,
    //         'bill_detail'                => $this->bill_detail,
    //         'po_details'                 => $this->po_details,
    //         'project_details'            => $this->project_details,
    //         'vendor_details'             => $this->vendor_details,
    //         'sub_project'                => $this->sub_project,
    //         'woBaseValue'                => $this->woBaseValue,
    //         'woGrossValue'               => $this->woGrossValue,
    //         'woTotalGstAmt'              => $this->WOtotalGstAmt,
    //         'subTotal'                   => $this->subTotal,
    //         'taxTotal'                   => $this->taxTotal,
    //         'grandTotal'                 => $this->grandTotal,
    //         // 'preparedBy'                 => $this->preparedBy,
    //         // 'checkby'                    => $this->checkby,
    //         // 'verifyby'                   => $this->verifyby,
    //         // 'approveby'                  => $this->approveby,
    //         'prUsers'                    => $this->prUsers,

    //         'NetPayableAmount'           => $this->NetPayableAmount,
    //         'NetTotalAmount'             => $this->NetTotalAmount,
    //     ]);

    //     // Set the filename dynamically using the bill ID or project name
    //     $filename = 'top_sheet_' . $this->project_details->project_name . '_' . $id;

    //     return [
    //         'pdf'      => $pdf,
    //         'fileName' => $filename,
    //     ];
    // }
    public function domPdfObjectForDownloadx($id)
    {
        // Create the PDF using DomPDF
        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $billId            = $id;
        $this->bill_detail = DB::table('purchase_bills')->where('id', $billId)->first();
        if (! $this->bill_detail) {
            abort(404, 'Bill not found');
        }

        $vendor_id = $this->bill_detail->purchase_vendor_id;
        $po_id     = $this->bill_detail->purchase_order_id;

        $this->po_details      = DB::table('purchase_orders')->where('id', $po_id)->first();
        $project_id            = $this->po_details->project_id;
        $sub_projectID         = $this->po_details->subproject_id;
        $this->project_details = DB::table('projects')->where('id', $project_id)->first();
        $this->sub_project     = DB::table('tasks')->where('id', $sub_projectID)->first();
        $this->vendor_details  = DB::table('purchase_vendors')->where('id', $vendor_id)->first();

        $this->purchase_items = DB::table('purchase_items')
            ->where('purchase_order_id', $po_id)
            ->selectRaw('SUM(amount) as total_amount, SUM(gst_amount) as total_gst, MAX(taxes_percent) as tax_percent')
            ->first();

        $this->woBaseValue   = $this->purchase_items->total_amount;
        $this->WOtotalGstAmt = $this->purchase_items->total_gst;
        $this->woGrossValue  = $this->woBaseValue + $this->WOtotalGstAmt;
        $this->WOtaxPercent  = $this->purchase_items->tax_percent;

        // ✅ Previous Bill Records
        $Previous_Bill_records = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->where('pb.purchase_order_id', $po_id)
            ->where('pb.id', '<', $billId)
            ->select('pb.*')
            ->get();

        // Release Hold Amounts
        $po_release_hold_amt = DB::table('release_hold_amt')
            ->where('po_id', $po_id)
            ->where('release_bill_id', '!=', $billId)
            ->get();

        $this->po_releaseHoldAmt = $po_release_hold_amt->isNotEmpty()
            ? array_sum($po_release_hold_amt->pluck('hold_amt')->toArray())
            : 0;

        $release_hold_amt = DB::table('release_hold_amt')
            ->where('release_bill_id', $billId)
            ->get();

        $this->releaseHoldAmt = $release_hold_amt->isNotEmpty()
            ? array_sum($release_hold_amt->pluck('hold_amt')->toArray())
            : 0;

        // Current Bill
        $curr_bill = DB::table('purchase_bill_items')
            ->where('purchase_bills_id', $billId)
            ->selectRaw('SUM(amount) as total_amount, SUM(tax_amount) as total_tax_amount,SUM(retention_cost) as total_retention_cost')
            ->first();

        $previousAmount = $this->getPriviousBillAmount($po_id, $billId);

        $this->subTotal        = $previousAmount['totalCertifiedAmount'];
        $this->taxTotal        = $previousAmount['totalTaxAmount'];
        $this->totalNetPayable = $previousAmount['totalNetPayable'];

        $this->com_po_releaseHoldAmt = $this->releaseHoldAmt + $this->po_releaseHoldAmt;
        $this->grandTotal            = $this->totalNetPayable + $this->po_releaseHoldAmt;

        $this->Currentsub_total      = $curr_bill->total_amount;
        $this->Currenttax_total      = $curr_bill->total_tax_amount;
        $this->Currentretention_cost = $curr_bill->total_retention_cost;
        $this->Currenttotal          = $this->Currentsub_total + $this->Currenttax_total + $this->releaseHoldAmt;

        $this->Cumulativesub_total = $this->subTotal + $this->Currentsub_total;
        $this->Cumulativetax_total = $this->taxTotal + $this->Currenttax_total;
        $this->Cumulativetotal     = $this->grandTotal + $this->Currenttotal;

        $this->AdvanceRecovery = $this->bill_detail->AdvanceRecovery;
        $this->HoldBillAmt     = $this->bill_detail->HoldBillAmt;

        // ✅ Retention Costs
        $this->previous_retentioncost = DB::table('purchase_bill_items as pbi')
            ->join('purchase_bills as pb', 'pb.id', '=', 'pbi.purchase_bills_id')
            ->where('pb.purchase_order_id', $po_id)
            ->where('pb.id', '<', $billId)
            ->sum('pbi.retention_cost');

        $this->total_retention_cost       = $this->previous_retentioncost + $this->Currentretention_cost;
        $this->totalAmtrecoveandretainion = $this->AdvanceRecovery + $this->Currentretention_cost + $this->HoldBillAmt;

        // ✅ Recoveries & Hold
        $this->previous_AdvanceRecovery = array_sum($Previous_Bill_records->pluck('AdvanceRecovery')->toArray());
        $this->total_AdvanceRecovery    = $this->previous_AdvanceRecovery + $this->AdvanceRecovery;

        $totalReleaseHoldAmtfromBillId = DB::table('release_hold_amt')
            ->where('po_id', $po_id)
            ->where('bill_id', '<', $billId)
            ->whereNotNull('release_bill_id')
            ->sum('hold_amt');

        $totalHoldAmt               = array_sum($Previous_Bill_records->pluck('HoldBillAmt')->toArray());
        $this->previous_HoldBillAmt = $totalHoldAmt - $totalReleaseHoldAmtfromBillId;
        $this->total_HoldBillAmt    = $this->previous_HoldBillAmt + $this->HoldBillAmt;

        $this->previous_totalAmtrecoveandretainion = $this->previous_AdvanceRecovery + $this->previous_retentioncost + $this->previous_HoldBillAmt;
        $this->post_totalAmtrecoveandretainion     = $this->previous_totalAmtrecoveandretainion + $this->totalAmtrecoveandretainion;

        // Materials
        $this->Steel       = $this->bill_detail->Steel;
        $this->Cement      = $this->bill_detail->Cement;
        $this->Electricity = $this->bill_detail->Electricity;

        // ✅ Previous Material Costs
        $this->previous_Steel       = array_sum($Previous_Bill_records->pluck('Steel')->toArray());
        $this->previous_Cement      = array_sum($Previous_Bill_records->pluck('Cement')->toArray());
        $this->previous_Electricity = array_sum($Previous_Bill_records->pluck('Electricity')->toArray());

        // Debits
        $instant_debit = DB::table('instant_debit')
            ->where('bill_id', $billId)
            ->where('bill_status', 2)
            ->sum('instantdebitamount') ?? 0;

        $previous_instant_debit = DB::table('instant_debit')
            ->where('po_id', $po_id)
            ->where('bill_status', 2)
            ->where('bill_id', '<', $billId)
            ->sum('instantdebitamount') ?? 0;

        // ✅ Other Debits
        $this->previous_otherDebits = array_sum($Previous_Bill_records->pluck('OtherDebits')->toArray())
         + array_sum($Previous_Bill_records->pluck('SafetyDebit')->toArray())
         + array_sum($Previous_Bill_records->pluck('QualityDebit')->toArray())
         + array_sum($Previous_Bill_records->pluck('Housekeeping')->toArray())
         + array_sum($Previous_Bill_records->pluck('PoorPerformance')->toArray())
             + $previous_instant_debit;

        $this->OtherDebits = $this->bill_detail->SafetyDebit
         + $this->bill_detail->QualityDebit
         + $this->bill_detail->Housekeeping
         + $this->bill_detail->PoorPerformance
         + $this->bill_detail->OtherDebits
             + $instant_debit;

        $this->total_otherDebits = $this->previous_otherDebits + $this->OtherDebits;

        // ✅ Debit Totals
        $this->previous_TotalDebitAmount = $this->previous_Steel + $this->previous_Cement + $this->previous_Electricity + $this->previous_otherDebits;
        $this->TotalDebitAmount          = $this->Steel + $this->Cement + $this->Electricity + $this->OtherDebits;
        $this->post_TotalDebitAmount     = $this->previous_TotalDebitAmount + $this->TotalDebitAmount;

        // ✅ Net Totals
        $this->NetPreviousTotal = $this->grandTotal - $this->previous_TotalDebitAmount - $this->previous_totalAmtrecoveandretainion;
        $this->NetPayableAmount = $this->Currenttotal - $this->totalAmtrecoveandretainion - $this->TotalDebitAmount;
        $this->NetTotalAmount   = $this->NetPreviousTotal + $this->NetPayableAmount;

        // Prepared By
        $this->preparedBy = DB::table('abstract_bypass as ab')
            ->join('users as u', 'u.id', '=', 'ab.user_id')
            ->where('ab.bill_id', $billId)
            ->select('u.name', 'u.signature')
            ->first();

        $priorityRoles = DB::table('priority_role')
            ->where('project_id', $project_id)
            ->get();

        $this->prUsers = DB::table(DB::raw("(SELECT * FROM priority_role WHERE project_id = $project_id ORDER BY priority_id DESC LIMIT 5) as pr"))
            ->join('users as u', 'u.id', '=', 'pr.user_id')
            ->join('employee_details as e', 'e.user_id', '=', 'pr.user_id')
            ->join('designations as d', 'd.id', '=', 'e.designation_id')
            ->orderBy('pr.priority_id', 'asc')
            ->select('pr.user_id', 'u.name as username', 'u.signature', 'd.name as designation', 'pr.priority_id')
            ->get();

        // Load PDF View
        $pdf->loadView('purchase::bills.pdf.topSheet', [
            'company_name'                        => $this->company->company_name,
            'company_gstin'                       => $this->company->gstin,
            'billId'                              => $billId,
            'WOtotalGstAmt'                       => $this->WOtotalGstAmt,
            'Currentsub_total'                    => $this->Currentsub_total,
            'Cumulativesub_total'                 => $this->Cumulativesub_total,
            'Currenttax_total'                    => $this->Currenttax_total,
            'Cumulativetax_total'                 => $this->Cumulativetax_total,
            'Currentretention_cost'               => $this->Currentretention_cost,
            'Currenttotal'                        => $this->Currenttotal,
            'po_releaseHoldAmt'                   => $this->po_releaseHoldAmt,
            'com_po_releaseHoldAmt'               => $this->com_po_releaseHoldAmt,
            'Cumulativetotal'                     => $this->Cumulativetotal,
            'AdvanceRecovery'                     => $this->AdvanceRecovery,
            'HoldBillAmt'                         => $this->HoldBillAmt,
            'totalAmtrecoveandretainion'          => $this->totalAmtrecoveandretainion,
            'Steel'                               => $this->Steel,
            'Cement'                              => $this->Cement,
            'Electricity'                         => $this->Electricity,
            'releaseHoldAmt'                      => $this->releaseHoldAmt,
            'OtherDebits'                         => $this->OtherDebits,
            'TotalDebitAmount'                    => $this->TotalDebitAmount,
            'NetPreviousTotal'                    => $this->NetPreviousTotal,
            'NetPayableAmount'                    => $this->NetPayableAmount,
            'bill_detail'                         => $this->bill_detail,
            'po_details'                          => $this->po_details,
            'project_details'                     => $this->project_details,
            'vendor_details'                      => $this->vendor_details,
            'sub_project'                         => $this->sub_project,
            'woBaseValue'                         => $this->woBaseValue,
            'woGrossValue'                        => $this->woGrossValue,
            'woTotalGstAmt'                       => $this->WOtotalGstAmt,
            'subTotal'                            => $this->subTotal,
            'taxTotal'                            => $this->taxTotal,
            'grandTotal'                          => $this->grandTotal,
            'prUsers'                             => $this->prUsers,
            'previous_AdvanceRecovery'            => $this->previous_AdvanceRecovery,
            'total_AdvanceRecovery'               => $this->total_AdvanceRecovery,
            'previous_HoldBillAmt'                => $this->previous_HoldBillAmt,
            'total_HoldBillAmt'                   => $this->total_HoldBillAmt,
            'previous_retentioncost'              => $this->previous_retentioncost,
            'previous_totalAmtrecoveandretainion' => $this->previous_totalAmtrecoveandretainion,
            'post_totalAmtrecoveandretainion'     => $this->post_totalAmtrecoveandretainion,
            'previous_Steel'                      => $this->previous_Steel,
            'previous_Cement'                     => $this->previous_Cement,
            'previous_Electricity'                => $this->previous_Electricity,
            'previous_otherDebits'                => $this->previous_otherDebits,
            'total_otherDebits'                   => $this->total_otherDebits,
            'previous_TotalDebitAmount'           => $this->previous_TotalDebitAmount,
            'post_TotalDebitAmount'               => $this->post_TotalDebitAmount,
            'NetPreviousTotal'                    => $this->NetPreviousTotal,
            'NetTotalAmount'                      => $this->NetTotalAmount,
            'NetPayableAmount'                    => $this->NetPayableAmount,
            'NetTotalAmount'                      => $this->NetTotalAmount,
            'total_retention_cost'                => $this->total_retention_cost,
            'total_steel'                         => $this->previous_Steel + $this->Steel,
            'total_cement'                        => $this->previous_Cement + $this->Cement,
            'total_electricity'                   => $this->previous_Electricity + $this->Electricity,
            'total_otherDebits'                   => $this->previous_otherDebits + $this->OtherDebits,
        ]);

        $filename = 'top_sheet_' . $this->project_details->project_name . '_' . $id;

        return [
            'pdf'      => $pdf,
            'fileName' => $filename,
        ];
    }


    public function bill_file_delete($id)
    {
        $file = DB::table('purchase_bills_files')->where('id', $id)->first();

        if ($file) {
            $filePath = public_path('bill-files/' . $file->stored_name);

            if (file_exists($filePath)) {
                unlink($filePath); // Delete file from disk
            }

            DB::table('purchase_bills_files')->where('id', $id)->delete(); // Delete DB record

            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'File deleted successfully.');
        }

        return response()->json(['success' => false], 404);
    }

}
