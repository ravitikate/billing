<?php
namespace Modules\Purchase\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use App\Mail\DynamicEmail;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\UnitType;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Purchase\DataTables\AbstractBillDataTable;
use Modules\Purchase\DataTables\BillApprovalDataTable;
use Modules\Purchase\DataTables\VendorCreditDataTable;
use Modules\Purchase\Entities\PurchaseBill;
use Modules\Purchase\Entities\PurchaseItem;
use Modules\Purchase\Entities\PurchaseOrder;
use Modules\Purchase\Entities\PurchasePaymentBill;
use Modules\Purchase\Entities\PurchaseProduct;
use Modules\Purchase\Entities\PurchaseSetting;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Entities\PurchaseVendorCredit;
use Modules\Purchase\Entities\PurchaseVendorCreditHistory;
use Modules\Purchase\Entities\PurchaseVendorCreditItemImage;
use Modules\Purchase\Entities\PurchaseVendorItem;
use Modules\Purchase\Events\VendorCreditPaymentMade;
use Modules\Purchase\Http\Requests\VendorCredits\UpdateVendorCreditRequest;
use App\Notifications\BillApproveByFinalAuthority;
use App\Models\User;


class VendorCreditController extends AccountBaseController
{

    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('purchase::app.menu.addVendorCredit');

        $this->middleware(function ($request, $next) {
            abort_403(! in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    public function index(VendorCreditDataTable $dataTable)
    {
        $this->pageTitle = __('purchase::app.menu.vendorCredits');
        $this->vendors   = PurchaseVendor::all();

        return $dataTable->render('purchase::vendor-credits.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request)
    {

        $this->id               = request('id');
        $this->getbill          = PurchaseBill::where('id', $this->id)->first();
        $this->vendor           = PurchaseVendor::all();
        $this->purchaseSetting  = PurchaseSetting::first();
        $this->zero             = '';
        $this->lastVendorCredit = PurchaseVendorCredit::lastVendorCreditNumber() + 1;
        $this->products         = PurchaseProduct::all();
        $this->categories       = ProductCategory::all();
        $this->units            = UnitType::all();
        $this->taxes            = Tax::all();
        $this->currencies       = Currency::all();
        $this->bills            = PurchaseBill::get();
        $this->orderId          = PurchaseBill::where('id', $request->id)->pluck('purchase_order_id')->toArray();
        $this->items            = PurchaseItem::with('taxes')->whereIn('purchase_order_id', $this->orderId)->get();
        $this->order            = PurchaseOrder::where('id', $this->orderId)->first();
        $this->companyCurrency  = Currency::where('id', company()->currency_id)->first();

        $items = $this->items->filter(function ($value, $key) {
            return $value->type == 'item';
        });

        /** @phpstan-ignore-next-line */
        $tax = $this->items->filter(function ($value, $key) {
            return $value->type == 'tax';
        });

        $this->totalTax     = $tax->sum('amount');
        $this->discount     = $this->order->discount;
        $this->discountType = $this->order->discount_type;

        if ($this->discountType == 'percent') {
            $this->totalDiscount = $items->sum('amount') * $this->discount / 100;
        }

        if ($this->discountType == 'fixed') {
            $this->totalDiscount = $this->discount;
        }

        if (request()->ajax()) {
            $html = view('purchase::vendor-credits.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::vendor-credits.ajax.create';

        return view('purchase::vendor-credits.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $items         = $request->item_name;
        $cost_per_item = $request->cost_per_item;
        $quantity      = $request->quantity;
        $amount        = $request->amount;

        if (! empty($items)) {
            foreach ($items as $itm) {
                if (is_null($itm)) {
                    return Reply::error(__('messages.itemBlank'));
                }
            }
        } else {
            return Reply::error(__('messages.addItem'));
        }

        foreach ($quantity as $qty) {
            if (! is_numeric($qty) && (intval($qty) < 1)) {
                return Reply::error(__('messages.quantityNumber'));
            }
        }

        foreach ($cost_per_item as $rate) {
            if (! is_numeric($rate)) {
                return Reply::error(__('messages.unitPriceNumber'));
            }
        }

        foreach ($amount as $amt) {
            if (! is_numeric($amt)) {
                return Reply::error(__('messages.amountNumber'));
            }
        }

        $vendorCredit                 = new PurchaseVendorCredit();
        $vendorCredit->vendor_id      = $request->vendor_id;
        $vendorCredit->credit_note_no = $request->credit_note_no;
        $vendorCredit->credit_date    = Carbon::createFromFormat($this->company->date_format, $request->vendor_credit_date)->format('Y-m-d');
        $vendorCredit->sub_total      = $request->sub_total;
        $vendorCredit->total          = $request->total;
        $vendorCredit->currency_id    = $request->currency_id;
        $vendorCredit->note           = trim_editor($request->note);
        $vendorCredit->discount       = round($request->discount_value, 2);
        $vendorCredit->discount_type  = $request->discount_type;
        $vendorCredit->bill_id        = $request->billId;
        $vendorCredit->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('vendor-credits.index');
        }

        return Reply::redirect($redirectUrl, __('messages.recordSaved'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {

        $this->vendorCredit = PurchaseVendorCredit::with('items', 'currency', 'unit', 'bills', 'bills.order.items.taxes', 'vendors')->findOrFail($id);
        if ($this->vendorCredit->discount > 0) {
            if ($this->vendorCredit->discount_type == 'percent') {
                $this->discount = (($this->vendorCredit->discount / 100) * $this->vendorCredit->sub_total);
            } else {
                $this->discount = $this->vendorCredit->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = [];

        $items = PurchaseVendorItem::whereNotNull('taxes')
            ->where('credit_id', $this->vendorCredit->id)
            ->get();

        $this->paidAmount = $this->vendorCredit->getPaidAmount();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = PurchaseVendorItem::taxbyid($tax)->first();

                if (! isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                    if ($this->vendorCredit->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->vendorCredit->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                    }

                } else {
                    if ($this->vendorCredit->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->vendorCredit->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->settings            = company();
        $this->vendorCreditSetting = invoice_setting();

        $this->view = 'purchase::vendor-credits.ajax.overview';
        $tab        = request('tab');

        switch ($tab) {
            case 'history':
                return $this->history($id);
            default:
                $this->view = 'purchase::vendor-credits.ajax.overview';
                break;
        }

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('purchase::vendor-credits.show', $this->data);

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request, $id)
    {
        $this->vendorCredit     = PurchaseVendorCredit::with('vendors', 'items', 'items.purchaseVendorCreditItemImage', 'bills', 'bills.order.items.taxes')->findOrFail($id);
        $this->vendor           = PurchaseVendor::all();
        $this->purchaseSetting  = PurchaseSetting::first();
        $this->invoiceSetting   = invoice_setting();
        $this->pageTitle        = $this->vendorCredit->vendors->primary_name;
        $this->currencies       = Currency::all();
        $this->categories       = ProductCategory::all();
        $this->units            = UnitType::all();
        $this->bills            = PurchaseBill::get();
        $this->products         = Product::all();
        $this->zero             = '';
        $this->lastVendorCredit = PurchaseVendorCredit::lastVendorCreditNumber() + 1;
        $this->getbill          = PurchaseBill::where('id', $id)->first();
        $this->taxes            = Tax::all();
        $this->companyCurrency  = Currency::where('id', company()->currency_id)->first();

        if (request()->ajax()) {

            $html = view('purchase::vendor-credits.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::vendor-credits.ajax.edit';

        return view('purchase::vendor-credits.create', $this->data);

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(UpdateVendorCreditRequest $request, $id)
    {
        $items         = $request->item_name;
        $cost_per_item = $request->cost_per_item;
        $hsn_sac_code  = $request->hsn_sac_code;
        $quantity      = $request->quantity;
        $amount        = $request->amount;
        $itemsSummary  = $request->item_summary;
        $tax           = $request->taxes;

        $vendorCredit              = PurchaseVendorCredit::findOrFail($id);
        $vendorCredit->credit_date = Carbon::createFromFormat($this->company->date_format, $request->vendor_credit_date)->format('Y-m-d');
        $vendorCredit->currency_id = $request->currency_id;
        $vendorCredit->note        = trim_editor($request->note);

        $vendorCredit->save();

        return Reply::redirect(route('vendor-credits.show', $vendorCredit->id), __('messages.updateSuccess'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $vendorCreditDelete = PurchaseVendorCredit::findOrFail($id);
        $vendorCreditDelete->delete();

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('vendor-credits.index')]);
    }

    public function addItem(Request $request)
    {
        $this->items = Product::findOrFail($request->id);

        $this->invoiceSetting = invoice_setting();

        $exchangeRate = Currency::findOrFail($request->currencyId);

        if (! is_null($exchangeRate) && ! is_null($exchangeRate->exchange_rate)) {
            if ($this->items->total_amount != '') {
                /** @phpstan-ignore-next-line */
                $this->items->price = floor($this->items->total_amount * $exchangeRate->exchange_rate);
            } else {

                $this->items->price = floatval($this->items->price) * floatval($exchangeRate->exchange_rate);
            }
        } else {
            if ($this->items->total_amount != '') {
                $this->items->price = $this->items->total_amount;
            }
        }

        $this->items->price = number_format((float) $this->items->price, 2, '.', '');
        $this->taxes        = Tax::all();
        $this->units        = UnitType::all();
        $view               = view('purchase::vendor-credits.ajax.add_item', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
            case 'delete':
                $this->deleteRecords($request);

                return Reply::success(__('messages.deleteSuccess'));
            default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {

        PurchaseVendorCredit::whereIn('id', explode(',', $request->row_ids))->delete();

        return true;
    }

    public function download($id)
    {
        $this->vendorCredit = PurchaseVendorCredit::with('items', 'currency', 'unit', 'bills', 'bills.order.items.taxes')->findOrFail($id);
        $pdfOption          = $this->domPdfObjectForDownload($id);
        $pdf                = $pdfOption['pdf'];
        $filename           = $pdfOption['fileName'];

        return $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->proposal       = PurchaseVendorCredit::with('items', 'vendors', 'currency', 'unit')->findOrFail($id);
        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        if ($this->proposal->discount > 0) {
            if ($this->proposal->discount_type == 'percent') {
                $this->discount = (($this->proposal->discount / 100) * $this->proposal->sub_total);
            } else {
                $this->discount = $this->proposal->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = [];

        $items = PurchaseVendorItem::whereNotNull('taxes')
            ->where('credit_id', $this->proposal->id)
            ->get();
        $this->invoiceSetting = invoice_setting();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = PurchaseVendorItem::taxbyid($tax)->first();

                if ($this->tax) {
                    if (! isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    } else {
                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->company = company();

        $pdf = app('dompdf.wrapper');

        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('purchase::vendor-credits.pdf.file', $this->data);

        $dom_pdf = $pdf->getDomPDF();
        $canvas  = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);
        $filename = __('purchase::app.menu.vendorCredit') . '-' . $this->proposal->id;

        return [
            'pdf'      => $pdf,
            'fileName' => $filename,
        ];
    }

    public function getBills(Request $request)
    {
        if ($request->id != 'null') {
            $getBills = PurchaseBill::where('purchase_vendor_id', $request->id)->where('status', 'paid')->get();
        } else {
            $getBills = PurchaseBill::get();
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $getBills]);
    }

    public function deleteCreditItemImage(Request $request)
    {
        $item = PurchaseVendorCreditItemImage::where('vendor_item_id', $request->invoice_item_id)->first();

        if ($item) {
            Files::deleteFile($item->hashname, 'vendorCredit-files/' . $item->id . '/');
            $item->delete();
        }

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function addBillItem(Request $request)
    {

        $this->orderId        = PurchaseBill::where('id', $request->id)->pluck('purchase_order_id')->toArray();
        $this->items          = PurchaseItem::whereIn('purchase_order_id', $this->orderId)->get();
        $this->invoiceSetting = invoice_setting();

        $exchangeRate = Currency::findOrFail($request->currencyId);

        if (! is_null($exchangeRate) && ! is_null($exchangeRate->exchange_rate)) {
            if ($this->items[0]->total_amount != '') {
                /** @phpstan-ignore-next-line */
                $this->items->price = floor($this->items[0]->total_amount * $exchangeRate->exchange_rate);
            } else {

                $this->items->price = floatval($this->items[0]->price) * floatval($exchangeRate->exchange_rate);
            }
        } else {
            if ($this->items->total_amount != '') {
                $this->items->price = $this->items->total_amount;
            }
        }

        $this->items->price = number_format((float) $this->items->price, 2, '.', '');
        $this->taxes        = Tax::all();
        $this->units        = UnitType::all();
        $view               = view('purchase::vendor-credits.ajax.add_bill_item', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function history($id)
    {
        $tab             = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->history   = PurchaseVendorCreditHistory::where('purchase_credit_id', $id)->orderByDesc('id')->get();
        $this->view      = 'purchase::vendor-credits.ajax.history';

        return view('purchase::vendor-credits.show', $this->data);
    }

    public function applyToBill($id)
    {
        $this->pageTitle       = __('purchase::app.applyToBill');
        $this->vendorCredit    = PurchaseVendorCredit::findOrFail($id);
        $this->nonPaidInvoices = PurchaseBill::select('purchase_bills.*')->where('credit_note', 0)
            ->where(function ($q) {
                $q->where('purchase_bills.status', 'open')
                    ->orWhere('purchase_bills.status', 'partially_paid');
            })
            ->where('purchase_vendor_id', $this->vendorCredit->vendor_id);

        $this->nonPaidInvoices = $this->nonPaidInvoices->with('purchasePaymentBills')->get();

        if (request()->ajax()) {
            $html = view('purchase::vendor-credits.ajax.apply_to_bill', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::vendor-credits.ajax.apply_to_bill';

        return view('purchase::vendor-credits.create', $this->data);
    }

    public function applyBillCredit(Request $request, $id)
    {
        $totalCreditAmount = 0.00;

        foreach ($request->invoices as $invoice) {
            $totalCreditAmount += $invoice['value'];
        }

        if ($totalCreditAmount == 0) {
            return Reply::error(__('messages.pleaseEnterCreditAmount'));
        }

        $creditNote        = PurchaseVendorCredit::findOrFail($id);
        $creditTotalAmount = 0.00;

        if ((float) $request->remainingAmount <= 0) {
            $creditNote->status = 'closed';
        }

        foreach ($request->invoices as $invoice) {
            if ($invoice['value'] !== '0' && ! is_null($invoice['value'])) {
                $creditTotalAmount += (float) $invoice['value'];
                $reqInvoice = PurchaseBill::findOrFail($invoice['invoiceId']);
                $this->makePayment($id, $invoice['invoiceId'], (float) $invoice['value']);
                if ($reqInvoice->total > $reqInvoice->amountPaid()) {
                    $reqInvoice->status = 'partially_paid';
                } else {
                    $reqInvoice->status = 'paid';
                }
                $dueAmount              = $reqInvoice->amountDue($creditNote->vendor_id);
                $reqInvoice->due_amount = $dueAmount;
                $reqInvoice->save();

            }
        }

        $creditNote->save();

        return Reply::redirect(route('vendor-credits.show', $creditNote->id), __('messages.creditNoteAppliedSuccessfully'));
    }

    public function makePayment($creditNoteId, $invoiceId, $amount)
    {
        $creditNote                          = PurchaseVendorCredit::findOrFail($creditNoteId);
        $payment                             = new PurchasePaymentBill();
        $payment->purchase_bill_id           = $invoiceId;
        $payment->purchase_vendor_credits_id = $creditNoteId;
        $payment->total_paid                 = $amount;
        $payment->gateway                    = 'Credit Note';
        $payment->purchase_vendor_id         = $creditNote->vendor_id;
        $payment->save();
        $remainingAmount = $creditNote->creditAmountRemaining();
        event(new VendorCreditPaymentMade($creditNote, $amount, $remainingAmount));
    }

    public function showBills(BillApprovalDataTable $dataTable)
    {
        $this->pageTitle = __('purchase::app.menu.BillApproval');
        $this->vendors   = PurchaseVendor::all();
        return $dataTable->render('purchase::approval.index', $this->data);
    }

    public function showAbstract(AbstractBillDataTable $dataTable)
    {
        $this->viewBillPermission = user()->permission('view_bill');
        $this->addBillPermission  = user()->permission('add_bill');
        $this->vendors            = PurchaseVendor::with('currency')->get();
        $this->pageTitle          = 'purchase::app.menu.billAbstract';
           $this->groups = DB::table('client_details')
            ->select('id', 'company_name')
            ->get();
        $this->projects = DB::table('projects')
            ->select('id', 'project_name')
            ->get();
        return $dataTable->render('purchase::bills.abstract', $this->data);
    }

    public function update_status(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');

        // Collect request data
        $id             = $request->row_id;
        $approveReject  = $request->approveReject;
        $approvalStatus = $request->approve_status;
        $project_id     = $request->project_id;
        $dept_id        = $request->dept_id;
        $priorityCount  = $request->priority_count;
        $message        = $request->message;
        $document_name  = $request->document_name;

        $uploaded_file_name = null;
        $file_original_name = null;

        // Handle file upload with proper storage
        if ($request->hasFile('file')) {
            $file               = $request->file('file');
            $file_original_name = $file->getClientOriginalName();
            $uploaded_file_name = $file->store('invoice-approval-files', 'public');
        }

        // Fetch invoice item
        $invoiceItem = DB::table('approval_items_by')
            ->where('id', $id)
            ->where('department_id', $dept_id)
            ->first();

        if (! $invoiceItem) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found.',
            ]);
        }
        $bill_item_id            = $invoiceItem->bill_item_id;
        $previousApproveStatusId = $invoiceItem->approve_status_id;

                                   // Process approval or rejection
        if ($approveReject == 2) { // Approval Case
            if ($previousApproveStatusId == $priorityCount) {
                DB::table('approval_items_by')
                    ->where('id', $id)
                    ->where('department_id', $dept_id)
                    ->update([
                        'status'             => 2,
                        'message'            => $message,
                        'uploaded_file_name' => $uploaded_file_name,
                        'file_original_name' => $file_original_name,
                        'updated_at'         => now(),
                    ]);

                DB::table('bill_approval_details')->insert([
                    'user_id'            => session('user')->id,
                    'bill_item_id'       => $bill_item_id,
                    'approval_status'    => $approveReject,
                    'message'            => $message,
                    'document_name'      => $document_name,
                    'uploaded_file_name' => $uploaded_file_name,
                    'file_original_name' => $file_original_name,
                    'created_at'         => now(),
                ]);
            } elseif ($previousApproveStatusId < $priorityCount) {
                $previousApproveStatusId++;
                DB::table('approval_items_by')
                    ->where('id', $id)
                    ->where('department_id', $dept_id)
                    ->update([
                        'approve_status_id'  => $previousApproveStatusId,
                        'message'            => $message,
                        'uploaded_file_name' => $uploaded_file_name,
                        'file_original_name' => $file_original_name,
                        'updated_at'         => now(),
                    ]);

                DB::table('bill_approval_details')->insert([
                    'user_id'            => session('user')->id,
                    'bill_item_id'       => $bill_item_id,
                    'approval_status'    => $approveReject,
                    'message'            => $message,
                    'document_name'      => $document_name,
                    'uploaded_file_name' => $uploaded_file_name,
                    'file_original_name' => $file_original_name,
                    'created_at'         => now(),
                ]);
            }
        } elseif ($approveReject == 3) { // Rejection Case
            if ($approvalStatus != 0) {
                $previousApproveStatusId = $approvalStatus;
            } elseif ($previousApproveStatusId > 1) {
                $previousApproveStatusId--;
            }

            DB::table('approval_items_by')
                ->where('id', $id)
                ->where('department_id', $dept_id)
                ->update([
                    'approve_status_id'  => $previousApproveStatusId,
                    'message'            => $message,
                    'uploaded_file_name' => $uploaded_file_name,
                    'file_original_name' => $file_original_name,
                    'updated_at'         => now(),
                ]);

            DB::table('bill_approval_details')->insert([
                'user_id'            => session('user')->id,
                'bill_item_id'       => $bill_item_id,
                'approval_status'    => $approveReject,
                'message'            => $message,
                'document_name'      => $document_name,
                'uploaded_file_name' => $uploaded_file_name,
                'file_original_name' => $file_original_name,
                'created_at'         => now(),
            ]);

            if ($previousApproveStatusId == $priorityCount) {
                DB::table('approval_items_by')
                    ->where('id', $id)
                    ->where('department_id', $dept_id)
                    ->update([
                        'status'             => 2,
                        'message'            => $message,
                        'uploaded_file_name' => $uploaded_file_name,
                        'file_original_name' => $file_original_name,
                        'updated_at'         => now(),
                    ]);

                DB::table('bill_approval_details')->insert([
                    'user_id'            => session('user')->id,
                    'bill_item_id'       => $bill_item_id,
                    'approval_status'    => $approveReject,
                    'message'            => $message,
                    'document_name'      => $document_name,
                    'uploaded_file_name' => $uploaded_file_name,
                    'file_original_name' => $file_original_name,
                    'created_at'         => now(),
                ]);
            }
        }

        // Fetch employee ID based on priority
        $employee = DB::table('priority_role')
            ->where('priority_id', $previousApproveStatusId)
            ->where('department_id', $invoiceItem->department_id)
            ->first();

        // Return response
        return response()->json([
            'success'     => true,
            'message'     => ($approveReject == 2) ? 'Invoice item approved successfully.' : 'Invoice item rejected successfully.',
            'employee_id' => $employee ? $employee->id : null,
        ]);
    }

    public function billDetails($id)
    {
        $userId       = session('user')->id;
        $role_id      = DB::table('role_user')->where('user_id', $userId)->first();
        $bill_item_id = $id;

        $invoiceItems = DB::table('bill_approval_details as ipd')
            ->join('users as u', 'u.id', '=', 'ipd.user_id')
            ->join('purchase_bill_items as it', 'it.id', '=', 'ipd.bill_item_id')
            ->join('approval_status  as aps', 'aps.id', '=', 'ipd.approval_status')
            ->where('ipd.bill_item_id', $bill_item_id)
            ->select('ipd.*', 'u.name', 'u.image', 'u.id as user_id', 'aps.status_name')
            ->get();

        $invoice_items = DB::table('purchase_bill_items as it')
            ->join('purchase_bills as i', 'i.id', '=', 'it.purchase_bills_id')
            ->select('it.*', 'i.purchase_bill_number')
            ->where('it.id', $bill_item_id)->first();

        $department = DB::table('purchase_bill_items as it')
            ->join('purchase_items as ci', 'ci.id', '=', 'it.purchase_bills_id')
            ->join('teams as t', 't.id', '=', 'ci.department_id')
            ->where('it.id', $bill_item_id)
            ->select('t.team_name', 't.id as department_id')
            ->first();

        // Pass data to the view
        $this->data['invoiceItems']  = $invoiceItems;
        $this->data['invoice_items'] = $invoice_items;
        $this->data['department']    = $department;

        return view('purchase::approval.bill_approval_details', $this->data);
    }
    
    
   public function update_status_abstract(Request $request)
    {

        DB::beginTransaction();
        try {
            $userId = session('user')->id;
            $billId = $request->bill_id;
            $bill   = DB::table('purchase_bills')->where('id', $billId)->first();
            $po     = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();

            $projectID    = $po->project_id;
            $priorityRole = DB::table('priority_role')
                ->where('project_id', $projectID)
                ->where('user_id', $userId)
                ->first();
            DB::table('approval_bills_by')->where('bill_id', $billId)->update([
                'approval_status' => 1,
                'message'         => 'Approver Is Not Available',
                'updated_at'      => now(),
            ]);
            DB::table('abstract_bypass')->insert([
                'bill_id'     => $billId,
                'user_id'     => $userId,
                'priority_id' => $priorityRole->priority_id,
            ]);
            if ($priorityRole->priority_id > 1) {
                for ($i = 1; $i < $priorityRole->priority_id; $i++) {
                    $prevRole = DB::table('priority_role')
                        ->where('project_id', $projectID)
                        ->where('priority_id', $i)
                        ->first();
                    if ($prevRole) {
                        $exists = DB::table('approval_bill_details')
                            ->where('bill_id', $billId)
                            ->where('user_id', $prevRole->user_id)
                            ->exists();
                        if (! $exists) {
                            DB::table('approval_bill_details')->insert([
                                'bill_id' => $billId,
                                'user_id' => $prevRole->user_id,
                                'bypass'  => 'bypass',
                                'message' => 'Approver Is Not Available',
                            ]);
                        }
                    }
                }
            }

           $invoiceId = DB::table('purchase_bills')->where('id', $billId)->value('reference_bill_no');

            if (is_null($invoiceId) || trim($invoiceId) === '') {
                $status = 'draft';
            } else {
                $status = 'pending';
            }

            DB::table('purchase_bills')->where('id', $billId)->update([
                'status'           => $status,
                'abstract_approve' => now('Asia/Kolkata'),
            ]);


            $existingApproval = DB::table('approval_bills_by')->where('bill_id', $billId)->first();
            $approvalData     = [
                'approval_status' => 1,
                'updated_at'      => now('Asia/Kolkata'),
            ];

            if ($existingApproval) {
                DB::table('approval_bills_by')->where('bill_id', $billId)->update($approvalData);
            } else {
                $approvalData['bill_id']    = $billId;
                $approvalData['created_at'] = now('Asia/Kolkata');
                DB::table('approval_bills_by')->insert($approvalData);
            }
            $priorityRoles = DB::table('priority_role')
                ->where('project_id', $projectID)
                ->get();
            if ($priorityRoles->isNotEmpty()) {
                $insertData = [];
                foreach ($priorityRoles as $role) {
                    $insertData[] = [
                        'bill_id' => $billId,
                        'user_id' => $role->user_id,
                        'bypass'  => '',
                        'message' => '',
                    ];
                }
                DB::table('approval_bill_details')->insert($insertData);
            }
            $vendor       = DB::table('purchase_vendors')->where('id', $bill->purchase_vendor_id)->first();
            $vendorUser   = DB::table('users')->where('id', $vendor->user_id)->first();
            $templateData = DB::table('email_templates')->where('template_key', 'abstract_bill_approve')->first();

            if (! $templateData) {
                throw new \Exception('Email template not found.');
            }

            $dynamicData = [
                'employee_name' => session('user')->name,
                'bill_no'       => 'Bill#' . str_pad($billId, 3, '0', STR_PAD_LEFT),
                'bill_date'     => $bill->bill_date,
                'vendor_name'   => $vendorUser->name ?? '',
                'sub_total'     => $bill->sub_total,
                'tax_total'     => $bill->tax_total,
                'total'         => $bill->total,
            ];

            $subject  = $templateData->subject;
            $template = $templateData->template;
            foreach ($dynamicData as $key => $value) {
                $subject  = str_replace("[$key]", $value, $subject);
                $template = str_replace("[$key]", $value, $template);
            }

            $emailData = [
                'subject' => $subject,
                'message' => $template,
            ];

            if (! empty($vendorUser->email)) {
                // Notification::route('mail', $vendorUser->email)
                //     ->notify(new SendInvoiceByContractor($emailData));
                Mail::to($vendorUser->email)->send(new DynamicEmail($emailData));

                $approver   = auth()->user();
                $notifyData = [
                    'bill_id'     => $bill->id,
                    'bill_no'     => $bill->purchase_bill_number,
                    'total'       => $bill->total,
                    'approved_by' => $approver->name,
                    'title'       => 'Abstract Approved',
                    'message'     => 'Bill #' . $bill->purchase_bill_number . ' approved by ' . $approver->name,
                ];

                $creator = User::find($vendorUser->id);
                if ($creator) {
                    $creator->notify(new BillApproveByFinalAuthority($notifyData));
                }

            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Status updated to draft successfully!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
   public function checkDuplicate(Request $request)
    {
        $request->validate([
            'invoiceno' => 'required|string',
            'bill_id' => 'sometimes|integer'
        ]);
    
        $query = PurchaseBill::where('reference_bill_no', $request->invoiceno);
    
        // If you're editing an existing bill, exclude it from the duplicate check
        if ($request->has('bill_id') && $request->bill_id) {
            $query->where('id', '!=', $request->bill_id);
        }
    
        $exists = $query->exists();
    
        return response()->json([
            'is_duplicate' => $exists,
            'message' => $exists ? 'This invoice number already exists.' : 'Invoice number is available.'
        ]);
    }


    public function submit_abstract_bill(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:purchase_bills,id',
        ]);

        try {
            // Update status to 'draft'
            DB::table('purchase_bills')
                ->where('id', $request->id)
                ->update([
                    'status'           => 'pending',
                    'abstract_approve' => Carbon::now('Asia/Kolkata')->toDateTimeString(),
                ]);

            return response()->json(['success' => true, 'message' => 'Status updated to draft successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // public function update_status_bill(Request $request)
    // {

    //     date_default_timezone_set("Asia/Kolkata");

    //     $id             = $request->row_id;
    //     $approveReject  = $request->approveReject;
    //     $approvalStatus = $request->approve_status;
    //     $project_id     = $request->project_id;
    //     $priorityCount  = $request->priority_count;
    //     $message        = $request->message;
    //     $document_name  = $request->document_name;

    //     $uploaded_file_name = null;
    //     $file_original_name = null;

    //     // print_r($priorityCount);exit;

    //     // Handle file upload with proper storage
    //     if ($request->hasFile('file')) {
    //         $file               = $request->file('file');
    //         $file_original_name = $file->getClientOriginalName();
    //         $uploaded_file_name = $file->store('bill-approval-files', 'public');
    //     }

    //     $invoiceItem = DB::table('approval_bills_by')
    //         ->where('id', $id)
    //         ->first();
    //     //    print_r(\DB::getQueryLog());
    //     if (! $invoiceItem) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Bill item not found.',
    //         ]);
    //     }

    //     $bill_item_id = $invoiceItem->bill_id;

    //     $suserId = session('user')->id;

    //     $p_role = DB::table('priority_role')->where('project_id', $project_id)->where('user_id', $suserId)->first();

    //     DB::table('approval_bills_by')->where('bill_id', $bill_item_id)->update([
    //         'approval_status' => $p_role->priority_id,
    //         'message'         => 'Approver Is Not Available',
    //         'updated_at'      => date('Y-m-d H:i:s'),
    //     ]);
    //     if ($p_role->priority_id > 1) {
    //         for ($i = 1; $i < $p_role->priority_id; $i++) {
    //             $pre_p_role = DB::table('priority_role')
    //                 ->where('project_id', $project_id)
    //                 ->where('priority_id', $i)
    //                 ->first();

    //             if ($pre_p_role) {
    //                 // Check if already exists
    //                 $exists = DB::table('approval_bill_details')
    //                     ->where('bill_id', $bill_item_id)
    //                     ->where('user_id', $pre_p_role->user_id)
    //                     ->exists();

    //                 if (! $exists) {
    //                     DB::table('approval_bill_details')->insert([
    //                         'bill_id' => $bill_item_id,
    //                         'user_id' => $pre_p_role->user_id,
    //                         'bypass'  => 'bypass',
    //                         'message' => $request->message_appnotavailable ?? 'Approver Is Not Available',
    //                     ]);
    //                 }
    //             }
    //         }
    //     }

    //     $updateResult = DB::table('approval_bills_by')
    //         ->where('id', $id)
    //         ->first();
    //     $previousApproveStatusId = $updateResult->approval_status; //priority

    //     if ($approveReject == 2) { // Approval Case

    //         if ($previousApproveStatusId == $priorityCount) {
    //             // print_r('hi1' . $previousApproveStatusId);exit;

    //             DB::table('approval_bills_by')
    //                 ->where('id', $id)
    //                 ->update([
    //                     'status'             => 2,
    //                     'message'            => $message,
    //                     'actual_status'      => 2,
    //                     'uploaded_file_name' => $uploaded_file_name,
    //                     'file_original_name' => $file_original_name,
    //                     'updated_at'         => now(),
    //                 ]);

    //             DB::table('approval_bill_details')->insert([
    //                 'user_id'            => session('user')->id,
    //                 'bill_id'            => $bill_item_id,
    //                 'approval_status'    => $approveReject,
    //                 'message'            => $message,
    //                 'document_name'      => $document_name,
    //                 'uploaded_file_name' => $uploaded_file_name,
    //                 'file_original_name' => $file_original_name,
    //                 'created_at'         => now(),
    //             ]);

    //             DB::table('purchase_bills')
    //                 ->where('id', $bill_item_id)
    //                 ->update([
    //                     'status'     => 'open',
    //                     'updated_at' => now(),
    //                 ]);

    //             $billDetails  = DB::table('purchase_bills')->where('id', $bill_item_id)->first();
    //             $poDetails    = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
    //             $projectID    = $poDetails->project_id;
    //             $subprojectID = $poDetails->subproject_id;

    //             // $p_role = DB::table('priority_role')->where('project_id', $project_id)->where('priority_id', $previousApproveStatusId)->first();
    //             $previous_p_role = DB::table('priority_role')->where('project_id', $project_id)->where('priority_id', $previousApproveStatusId)->first();
    //             // $userID = $p_role->user_id;
    //             $previoususerID = $previous_p_role->user_id;

    //             // $employeeDetail = DB::table('users')->where('id', $userID)->first();
    //             $previousemployeeDetail = DB::table('users')->where('id', $previoususerID)->first();

    //             $vDetail      = DB::table('purchase_vendors')->where('id', $billDetails->purchase_vendor_id)->first();
    //             $vendorDetail = DB::table('users')->where('id', $vDetail->user_id)->first();

    //             $project      = DB::table('projects')->where('id', $projectID)->first();
    //             $project_name = $project ? $project->project_name : null;

    //             $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
    //             $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

    //             $email_templates_vendor = DB::table('email_templates')->where('template_key', 'bill_approve_by_final_employee_for_vendor')->first();

    //             if (! $email_templates_vendor) {
    //                 return response()->json(['success' => false, 'message' => 'Email template not found.']);
    //             }

    //             $dynamicData = [
    //                 'approve_by_employee_name' => $previousemployeeDetail->name,
    //                 // 'employee_name_nextpriority'   => $employeeDetail->name,
    //                 'bill_no'                  => 'Bill#' . str_pad($bill_item_id, 3, '0', STR_PAD_LEFT),
    //                 'bill_date'                => $billDetails->bill_date,
    //                 'vendor_name'              => $vendorDetail->name,
    //                 'sub_total'                => $billDetails->sub_total,
    //                 'tax_total'                => $billDetails->tax_total,
    //                 'total'                    => $billDetails->total,
    //             ];

    //             try {
    //                 if (! empty($vendorDetail->email)) {
    //                     $subject_vendor  = $email_templates_vendor->subject;
    //                     $template_vendor = $email_templates_vendor->template;

    //                     foreach ($dynamicData as $key => $value) {
    //                         $subject_vendor  = str_replace("[$key]", $value, $subject_vendor);
    //                         $template_vendor = str_replace("[$key]", $value, $template_vendor);
    //                     }

    //                     $emailDataVendor = [
    //                         'subject'    => $subject_vendor,
    //                         'greeting'   => 'Hi there!',
    //                         'message'    => $template_vendor,
    //                         'closing'    => 'We appreciate your support!',
    //                         'salutation' => 'Cheers, The Team',
    //                     ];

    //                     Notification::route('mail', $vendorDetail->email)
    //                         ->notify(new SendInvoiceByContractor($emailDataVendor));
    //                 }
    //             } catch (\Exception $e) {
    //                 return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
    //             }

    //         } elseif ($previousApproveStatusId < $priorityCount) {
    //             // print_r('hi' . $previousApproveStatusId);exit;

    //             $prUidpre = DB::table('priority_role')
    //                 ->where('project_id', $project_id)
    //                 ->where('priority_id', $previousApproveStatusId - 1)
    //                 ->first();
    //             // print_r($prUidpre->user_id);exit;

    //             $prUidnext = DB::table('priority_role')
    //                 ->where('project_id', $project_id)
    //                 ->where('priority_id', $previousApproveStatusId + 1)
    //                 ->first();

    //             $previousPriority = $previousApproveStatusId;
    //             $previousApproveStatusId++;
    //             DB::table('approval_bills_by')
    //                 ->where('id', $id)
    //                 ->update([
    //                     'approval_status'    => $previousApproveStatusId,
    //                     'message'            => $message,
    //                     'actual_status'      => 2,
    //                     'uploaded_file_name' => $uploaded_file_name,
    //                     'file_original_name' => $file_original_name,
    //                     'updated_at'         => now(),
    //                 ]);
    //             if (isset($prUidpre->user_id)) {
    //                 // Check if an approval record exists with the same user and bill, and approval_status is NULL
    //                 $exist = DB::table('approval_bill_details')
    //                     ->where('user_id', $prUidpre->user_id)
    //                     ->where('bill_id', $bill_item_id)
    //                     ->orderByDesc('id')
    //                     ->first();
    //     // print_r($exist);exit;
    //                 if ($exist->approval_status === null) {
    //                     // Insert a new bypass record because the approver is not available
    //                     DB::table('approval_bill_details')->insert([
    //                         'bill_id' => $bill_item_id,
    //                         'user_id' => $prUidpre->user_id,
    //                         'bypass'  => 'bypass',
    //                         'message' => $request->message_appnotavailable ?? 'Approver Is Not Available',
    //                     ]);
    //                 }
    //             }

    //             DB::table('approval_bill_details')->insert([
    //                 'user_id'            => session('user')->id,
    //                 'bill_id'            => $bill_item_id,
    //                 'approval_status'    => $approveReject,
    //                 'message'            => $message,
    //                 'document_name'      => $document_name,
    //                 'uploaded_file_name' => $uploaded_file_name,
    //                 'file_original_name' => $file_original_name,
    //                 'created_at'         => now(),
    //             ]);

    //             DB::table('approval_bill_details')->insert([
    //                 'user_id'    => $prUidnext->user_id,
    //                 'bill_id'    => $bill_item_id,
    //                 'message'    => 'Re Assigned',
    //                 'created_at' => now(),
    //             ]);

    //             $billDetails  = DB::table('purchase_bills')->where('id', $bill_item_id)->first();
    //             $poDetails    = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
    //             $projectID    = $poDetails->project_id;
    //             $subprojectID = $poDetails->subproject_id;

    //             $p_role          = DB::table('priority_role')->where('project_id', $project_id)->where('priority_id', $previousApproveStatusId)->first();
    //             $previous_p_role = DB::table('priority_role')->where('project_id', $project_id)->where('priority_id', $previousPriority)->first();
    //             $userID          = $p_role->user_id;
    //             $previoususerID  = $previous_p_role->user_id;

    //             $employeeDetail         = DB::table('users')->where('id', $userID)->first();
    //             $previousemployeeDetail = DB::table('users')->where('id', $previoususerID)->first();

    //             $vDetail      = DB::table('purchase_vendors')->where('id', $billDetails->purchase_vendor_id)->first();
    //             $vendorDetail = DB::table('users')->where('id', $vDetail->user_id)->first();

    //             $project      = DB::table('projects')->where('id', $projectID)->first();
    //             $project_name = $project ? $project->project_name : null;

    //             $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
    //             $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

    //             $email_templates        = DB::table('email_templates')->where('template_key', 'bill_approve_by_employee')->first();
    //             $email_templates_vendor = DB::table('email_templates')->where('template_key', 'bill_approve_by_employee_for_vendor')->first();

    //             if (! $email_templates || ! $email_templates_vendor) {
    //                 return response()->json(['success' => false, 'message' => 'Email template not found.']);
    //             }

    //             $dynamicData = [
    //                 'approve_by_employee_name'   => $previousemployeeDetail->name,
    //                 'employee_name_nextpriority' => $employeeDetail->name,
    //                 'bill_no'                    => 'Bill#' . str_pad($bill_item_id, 3, '0', STR_PAD_LEFT),
    //                 'bill_date'                  => $billDetails->bill_date,
    //                 'vendor_name'                => $vendorDetail->name,
    //                 'sub_total'                  => $billDetails->sub_total,
    //                 'tax_total'                  => $billDetails->tax_total,
    //                 'total'                      => $billDetails->total,
    //             ];

    //             try {
    //                 // Send email to Employee
    //                 if (! empty($employeeDetail->email)) {
    //                     $subject_emp  = $email_templates->subject;
    //                     $template_emp = $email_templates->template;

    //                     foreach ($dynamicData as $key => $value) {
    //                         $subject_emp  = str_replace("[$key]", $value, $subject_emp);
    //                         $template_emp = str_replace("[$key]", $value, $template_emp);
    //                     }

    //                     $emailDataEmp = [
    //                         'subject'    => $subject_emp,
    //                         'greeting'   => 'Hi there!',
    //                         'message'    => $template_emp,
    //                         'closing'    => 'We appreciate your support!',
    //                         'salutation' => 'Cheers, The Team',
    //                     ];

    //                     Notification::route('mail', $employeeDetail->email)
    //                         ->notify(new SendInvoiceByContractor($emailDataEmp));
    //                 }

    //                 // Send email to Vendor
    //                 if (! empty($vendorDetail->email)) {
    //                     $subject_vendor  = $email_templates_vendor->subject;
    //                     $template_vendor = $email_templates_vendor->template;

    //                     foreach ($dynamicData as $key => $value) {
    //                         $subject_vendor  = str_replace("[$key]", $value, $subject_vendor);
    //                         $template_vendor = str_replace("[$key]", $value, $template_vendor);
    //                     }

    //                     $emailDataVendor = [
    //                         'subject'    => $subject_vendor,
    //                         'greeting'   => 'Hi there!',
    //                         'message'    => $template_vendor,
    //                         'closing'    => 'We appreciate your support!',
    //                         'salutation' => 'Cheers, The Team',
    //                     ];

    //                     Notification::route('mail', $vendorDetail->email)
    //                         ->notify(new SendInvoiceByContractor($emailDataVendor));
    //                 }

    //             } catch (\Exception $e) {
    //                 return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
    //             }
    //         }
    //     } elseif ($approveReject == 3) { // Rejection Case

    //         $userID  = session('user')->id;
    //         $np_role = DB::table('priority_role')->where('project_id', $project_id)->where('user_id', $userID)->first();

    //         $currentpriority = $np_role->priority_id;

    //         // priorityCount = 3

    //         if ($currentpriority > 1) {
    //             $previousApproveStatusId = $currentpriority - 1;
    //         } else {
    //             $previousApproveStatusId = $currentpriority;
    //         }

    //         DB::table('approval_bills_by')
    //             ->where('id', $id)
    //             ->update([
    //                 'approval_status'    => $previousApproveStatusId,
    //                 'message'            => $message,
    //                 'actual_status'      => 3,
    //                 'uploaded_file_name' => $uploaded_file_name,
    //                 'file_original_name' => $file_original_name,
    //                 'updated_at'         => now(),
    //             ]);

    //         DB::table('approval_bill_details')->insert([
    //             'user_id'            => session('user')->id,
    //             'bill_id'            => $bill_item_id,
    //             'approval_status'    => $approveReject,
    //             'message'            => $message,
    //             'document_name'      => $document_name,
    //             'uploaded_file_name' => $uploaded_file_name,
    //             'file_original_name' => $file_original_name,
    //             'created_at'         => now(),
    //         ]);

    //         $billDetails = DB::table('purchase_bills')->where('id', $bill_item_id)->first();
    //         $poDetails   = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
    //         // $projectID    = $poDetails->project_id;
    //         $subprojectID = $poDetails->subproject_id;

    //         // $currentApproveStatusId = $currentpriority;
    //         $previous_p_role = DB::table('priority_role')->where('project_id', $project_id)->where('priority_id', $previousApproveStatusId)->first();
    //         $previoususerID  = $previous_p_role->user_id;

    //         DB::table('approval_bill_details')->insert([
    //             'user_id'    => $previoususerID,
    //             'bill_id'    => $bill_item_id,
    //             'message'    => 'Re Assigned',
    //             'created_at' => now(),
    //         ]);

    //         $employeeDetail         = DB::table('users')->where('id', $userID)->first();
    //         $previousemployeeDetail = DB::table('users')->where('id', $previoususerID)->first();

    //         $vDetail      = DB::table('purchase_vendors')->where('id', $billDetails->purchase_vendor_id)->first();
    //         $vendorDetail = DB::table('users')->where('id', $vDetail->user_id)->first();

    //         $project      = DB::table('projects')->where('id', $project_id)->first();
    //         $project_name = $project ? $project->project_name : null;

    //         $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
    //         $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

    //         $email_templates        = DB::table('email_templates')->where('template_key', 'bill_reject_by_employee')->first();
    //         $email_templates_vendor = DB::table('email_templates')->where('template_key', 'bill_rejected_by_employee_for_vendor')->first();

    //         if (! $email_templates || ! $email_templates_vendor) {
    //             return response()->json(['success' => false, 'message' => 'Email template not found.']);
    //         }

    //         $dynamicData = [
    //             'employee_name_previous_priority' => $previousemployeeDetail->name,
    //             'rejected_by_employee_name'       => $employeeDetail->name,
    //             'bill_no'                         => 'Bill#' . str_pad($bill_item_id, 3, '0', STR_PAD_LEFT),
    //             'bill_date'                       => $billDetails->bill_date,
    //             'vendor_name'                     => $vendorDetail->name,
    //             'sub_total'                       => $billDetails->sub_total,
    //             'tax_total'                       => $billDetails->tax_total,
    //             'total'                           => $billDetails->total,
    //             'rejection_note'                  => $message,
    //         ];

    //         try {
    //             // Send email to Employee
    //             if (! empty($previousemployeeDetail->email)) {
    //                 $subject_emp  = $email_templates->subject;
    //                 $template_emp = $email_templates->template;

    //                 foreach ($dynamicData as $key => $value) {
    //                     $subject_emp  = str_replace("[$key]", $value, $subject_emp);
    //                     $template_emp = str_replace("[$key]", $value, $template_emp);
    //                 }

    //                 $emailDataEmp = [
    //                     'subject'    => $subject_emp,
    //                     'greeting'   => 'Hi there!',
    //                     'message'    => $template_emp,
    //                     'closing'    => 'We appreciate your support!',
    //                     'salutation' => 'Cheers, The Team',
    //                 ];

    //                 Notification::route('mail', $previousemployeeDetail->email)
    //                     ->notify(new SendInvoiceByContractor($emailDataEmp));
    //             }

    //             // Send email to Vendor
    //             if (! empty($vendorDetail->email)) {
    //                 $subject_vendor  = $email_templates_vendor->subject;
    //                 $template_vendor = $email_templates_vendor->template;

    //                 foreach ($dynamicData as $key => $value) {
    //                     $subject_vendor  = str_replace("[$key]", $value, $subject_vendor);
    //                     $template_vendor = str_replace("[$key]", $value, $template_vendor);
    //                 }

    //                 $emailDataVendor = [
    //                     'subject'    => $subject_vendor,
    //                     'greeting'   => 'Hi there!',
    //                     'message'    => $template_vendor,
    //                     'closing'    => 'We appreciate your support!',
    //                     'salutation' => 'Cheers, The Team',
    //                 ];

    //                 Notification::route('mail', $vendorDetail->email)
    //                     ->notify(new SendInvoiceByContractor($emailDataVendor));
    //             }

    //         } catch (\Exception $e) {
    //             return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
    //         }

    //         if ($previousApproveStatusId == $priorityCount) {
    //             DB::table('approval_bills_by')
    //                 ->where('id', $id)
    //                 ->update([
    //                     'status'             => 2,
    //                     'message'            => $message,
    //                     'uploaded_file_name' => $uploaded_file_name,
    //                     'file_original_name' => $file_original_name,
    //                     'updated_at'         => now(),
    //                 ]);

    //             DB::table('approval_bill_details')->insert([
    //                 'user_id'            => session('user')->id,
    //                 'bill_id'            => $bill_item_id,
    //                 'approval_status'    => $approveReject,
    //                 'message'            => $message,
    //                 'document_name'      => $document_name,
    //                 'uploaded_file_name' => $uploaded_file_name,
    //                 'file_original_name' => $file_original_name,
    //                 'created_at'         => now(),
    //             ]);

    //             $billDetails  = DB::table('purchase_bills')->where('id', $bill_item_id)->first();
    //             $poDetails    = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
    //             $projectID    = $poDetails->project_id;
    //             $subprojectID = $poDetails->subproject_id;

    //               $userID = session('user')->id;
    //              $employeeDetail = DB::table('users')->where('id', $userID)->first();
    //             // $previousemployeeDetail = DB::table('users')->where('id', $previoususerID)->first();

    //             $vDetail      = DB::table('purchase_vendors')->where('id', $billDetails->purchase_vendor_id)->first();
    //             $vendorDetail = DB::table('users')->where('id', $vDetail->user_id)->first();

    //             $project      = DB::table('projects')->where('id', $projectID)->first();
    //             $project_name = $project ? $project->project_name : null;

    //             $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
    //             $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

    //             $email_templates_vendor = DB::table('email_templates')->where('template_key', 'bill_rejected_by_employee_for_vendor')->first();

    //             if (! $email_templates_vendor) {
    //                 return response()->json(['success' => false, 'message' => 'Email template not found.']);
    //             }

    //             $dynamicData = [
    //                 // 'employee_name_previous_priority' => $previousemployeeDetail->name,
    //                 'rejected_by_employee_name' => $employeeDetail->name,
    //                 'bill_no'                   => 'Bill#' . str_pad($bill_item_id, 3, '0', STR_PAD_LEFT),
    //                 'bill_date'                 => $billDetails->bill_date,
    //                 'vendor_name'               => $vendorDetail->name,
    //                 'sub_total'                 => $billDetails->sub_total,
    //                 'tax_total'                 => $billDetails->tax_total,
    //                 'total'                     => $billDetails->total,
    //                 'rejection_note'            => $message,
    //             ];

    //             try {

    //                 // Send email to Vendor
    //                 if (! empty($vendorDetail->email)) {
    //                     $subject_vendor  = $email_templates_vendor->subject;
    //                     $template_vendor = $email_templates_vendor->template;

    //                     foreach ($dynamicData as $key => $value) {
    //                         $subject_vendor  = str_replace("[$key]", $value, $subject_vendor);
    //                         $template_vendor = str_replace("[$key]", $value, $template_vendor);
    //                     }

    //                     $emailDataVendor = [
    //                         'subject'    => $subject_vendor,
    //                         'greeting'   => 'Hi there!',
    //                         'message'    => $template_vendor,
    //                         'closing'    => 'We appreciate your support!',
    //                         'salutation' => 'Cheers, The Team',
    //                     ];

    //                     Notification::route('mail', $vendorDetail->email)
    //                         ->notify(new SendInvoiceByContractor($emailDataVendor));
    //                 }

    //             } catch (\Exception $e) {
    //                 return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
    //             }
    //         }
    //     }

    //     // Fetch employee ID based on priority
    //     $employee = DB::table('priority_role')
    //         ->where('priority_id', $previousApproveStatusId)
    //     //    ->where('department_id', $invoiceItem->department_id)
    //         ->first();

    //     // Return response
    //     return response()->json([
    //         'success'     => true,
    //         'message'     => ($approveReject == 2) ? 'Bill item approved successfully.' : 'Bill item rejected successfully.',
    //         'employee_id' => $employee ? $employee->id : null,
    //     ]);
    // }

    public function bulkApproval(Request $request)
    {
        $ids = explode(',', $request->input('row_ids', ''));

        foreach ($ids as $id) {
            // Step 1: Fetch the project_id from purchase_bills and purchase_orders tables
            $projectId = DB::table('purchase_bills as pb')
                ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
                ->where('pb.id', $id)
                ->value('po.project_id');

            // Step 2: Count the number of rows in the priority_role table for the fetched project_id
            $priorityCount = DB::table('priority_role')
                ->where('project_id', $projectId)
                ->count('id');

            // Step 3: Fetch the invoice item to proceed with approval
            $invoiceItem = DB::table('approval_bills_by')
                ->where('bill_id', $id)
                ->first();

            if (! $invoiceItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bill item not found.',
                ]);
            }

            // Step 4: Get relevant values from the invoice item
            $bill_item_id            = $invoiceItem->bill_id;
            $previousApproveStatusId = $invoiceItem->approval_status;

                                 // Process approval or rejection based on the previous status and priority count
            $approvalStatus = 2; // Approved
            $message        = 'Approved';

            if ($previousApproveStatusId == $priorityCount) {
                // If approval status is equal to priority count
                DB::table('approval_bills_by')
                    ->where('id', $invoiceItem->id)
                    ->update([
                        'status'        => $approvalStatus,
                        'message'       => $message,
                        'actual_status' => $approvalStatus,
                        'updated_at'    => now(),
                    ]);

                DB::table('approval_bill_details')->insert([
                    'user_id'         => session('user')->id,
                    'bill_id'         => $bill_item_id,
                    'approval_status' => $approvalStatus,
                    'message'         => $message,
                    'created_at'      => now(),
                ]);

                DB::table('purchase_bills')
                    ->where('id', $bill_item_id)
                    ->update([
                        'status'     => 'open',
                        'updated_at' => now(),
                    ]);

            } elseif ($previousApproveStatusId < $priorityCount) {
                // If approval status is less than the priority count
                $previousApproveStatusId++;
                DB::table('approval_bills_by')
                    ->where('id', $invoiceItem->id)
                    ->update([
                        'approval_status' => $previousApproveStatusId,
                        'message'         => $message,
                        'actual_status'   => $approvalStatus,
                        'updated_at'      => now(),
                    ]);

                DB::table('approval_bill_details')->insert([
                    'user_id'         => session('user')->id,
                    'bill_id'         => $bill_item_id,
                    'approval_status' => $approvalStatus,
                    'message'         => $message,
                    'created_at'      => now(),
                ]);
            }
        }

        // Redirect the user to the last location after the bulk approval process
        return redirect()->back()->with('success', 'Bulk approval completed successfully!');
    }

    public function sendMessage(Request $request)
    {
        $message = $request->input('message');
        $billId  = $request->input('bill_id');
        $userId  = session('user')->id;

        $data = [
            'user_id' => $userId,
            'bill_id' => $billId,
            'message' => $message,
        ];

        $q = DB::table('bill_chatting')->insert($data);

        if ($q) {
            $chat = DB::table('bill_chatting')
                ->join('users', 'users.id', '=', 'bill_chatting.user_id')
                ->where('bill_chatting.bill_id', $billId)
                ->select('bill_chatting.*', 'users.name as user_name')
                ->orderBy('bill_chatting.created_at')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'chat'    => $chat,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Message not sent.']);
    }

    public function getMessages(Request $request)
    {

        $billId = $request->input('bill_id');
        $userId = session('user')->id;

        $vendor = DB::table('purchase_vendors')
            ->where('user_id', $userId)
            ->first();
        $employee_d = DB::table('employee_details')
            ->where('user_id', $userId)
            ->first();

        if ($vendor) {
            $q = DB::table('bill_chatting')->where('bill_id', $billId)->update(['vendor_status' => 'read']);
        } elseif ($employee_d) {
            $q = DB::table('bill_chatting')->where('bill_id', $billId)->update(['employee_status' => 'read']);
        }

        $chat = DB::table('bill_chatting')
            ->join('users', 'users.id', '=', 'bill_chatting.user_id')
            ->where('bill_chatting.bill_id', $billId)
            ->select('bill_chatting.*', 'users.name as user_name')
            ->orderBy('bill_chatting.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'chat'    => $chat,
        ]);
    }

    public function approverIsNototAvailable(Request $request)
    {
        $userId = session('user')->id;

        $billId = $request->bill_id;

        $billDetails = DB::table('purchase_bills')->where('id', $billId)->first();
        $poDetails   = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
        $projectID   = $poDetails->project_id;
        // print_r($billId);exit;
        $p_role = DB::table('priority_role')->where('project_id', $projectID)->where('user_id', $userId)->first();

        DB::table('abstract_bypass')->insert([
            'bill_id'     => $billId,
            'user_id'     => $userId,
            'priority_id' => $p_role->priority_id,
            'message'     => $request->message_appnotavailable,
        ]);

        if ($p_role->priority_id > 1) {
            for ($i = 1; $i < $p_role->priority_id; $i++) {
                $pre_p_role = DB::table('priority_role')
                    ->where('project_id', $projectID)
                    ->where('priority_id', $i)
                    ->first();

                if ($pre_p_role) {
                    // Check if already exists
                    $exists = DB::table('approval_bill_details')
                        ->where('bill_id', $billId)
                        ->where('user_id', $pre_p_role->user_id)
                        ->exists();

                    if (! $exists) {
                        DB::table('approval_bill_details')->insert([
                            'bill_id' => $billId,
                            'user_id' => $pre_p_role->user_id,
                            'bypass'  => 'bypass',
                            'message' => $request->message_appnotavailable ?? 'Approver Is Not Available',
                        ]);
                    }
                }
            }
        }

        return redirect()->route('vendor-credits.show-abstract')->with('success', 'Submit successfully.');

    }

    public function approverIsNototAvailablePermision(Request $request)
    {
        date_default_timezone_set("Asia/Kolkata");
        $userId = session('user')->id;
        $billId = $request->bill_id;

        $billDetails = DB::table('purchase_bills')->where('id', $billId)->first();
        $poDetails   = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
        $projectID   = $poDetails->project_id;

        $p_role = DB::table('priority_role')->where('project_id', $projectID)->where('user_id', $userId)->first();

        DB::table('approval_bills_by')->where('bill_id', $billId)->update([
            'approval_status' => $p_role->priority_id,
            'message'         => 'Approver Is Not Available',
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        if ($p_role->priority_id > 1) {
            for ($i = 1; $i < $p_role->priority_id; $i++) {
                $pre_p_role = DB::table('priority_role')
                    ->where('project_id', $projectID)
                    ->where('priority_id', $i)
                    ->first();

                if ($pre_p_role) {
                    // Check if already exists
                    $exists = DB::table('approval_bill_details')
                        ->where('bill_id', $billId)
                        ->where('user_id', $pre_p_role->user_id)
                        ->exists();

                    if (! $exists) {
                        DB::table('approval_bill_details')->insert([
                            'bill_id' => $billId,
                            'user_id' => $pre_p_role->user_id,
                            'bypass'  => 'bypass',
                            'message' => $request->message_appnotavailable ?? 'Approver Is Not Available',
                        ]);
                    }
                }
            }
        }

        return redirect()->route('bills.bill-approval')->with('success', 'Submit successfully.');

    }

    public function getVendorDetails(Request $request)
    {

        $this->vendors = DB::table('purchase_orders as po')
            ->join('purchase_vendors as v', 'v.id', '=', 'po.vendor_id')
            ->where('po.project_id', $request->project_id)
            ->where('po.subproject_id', $request->subproject_id)
            ->select('po.vendor_id', 'v.primary_name')
            ->distinct()
            ->get();

        return response()->json($this->vendors);
    }

    public function getBillBreakdown($id)
    {
        $billId = $id;

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

        return response()->json([
            'steel'                 => $bill_detail->Steel ?? 0,
            'cement'                => $bill_detail->Cement ?? 0,
            'electricity'           => $bill_detail->Electricity ?? 0,
            'safetydebit'           => $bill_detail->SafetyDebit ?? 0,
            'qualitydebit'          => $bill_detail->QualityDebit ?? 0,
            'housekeeping'          => $bill_detail->Housekeeping ?? 0,
            'poorperformance'       => $bill_detail->PoorPerformance ?? 0,
            'otherdebit'            => $bill_detail->OtherDebits ?? 0,
            'instantdebit'          => $instant_debit,
            'holdbillamt'           => $bill_detail->HoldBillAmt ?? 0,
            'advancerecovery'       => $bill_detail->AdvanceRecovery ?? 0,
            'currentsub_total'      => $Currentsub_total,
            'currenttax_total'      => $Currenttax_total,
            'currentretention_cost' => $Currentretention_cost,
            'currenttotal'          => $Currenttotal,
            'totalrecoveries'       => $totalRecoveries,
            'totaldebitamount'      => $TotalDebitAmount,
            'netpayableamount'      => $NetPayableAmount,
            'releaseHoldAmt'        => $releaseHoldAmt,
            'bill_id'               => $billId,

        ]);
    }

    public function submitaddinstantDebitAmount(Request $request)
    {
        $userId             = session('user')->id;
        $file_relative_path = null;
        $file_original_name = null;

        if ($request->hasFile('file')) {
            $file               = $request->file('file');
            $file_original_name = $file->getClientOriginalName();
            // Define target path in public directory
            $destinationPath = public_path('user-uploads/instant_debit');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $file_original_name);
            $file_relative_path = 'user-uploads/instant_debit/' . $file_original_name;
        }

        $insert_id = DB::table('instant_debit')->insertGetId([
            'user_id'            => $userId,
            'project_id'         => $request->project_id,
            'subproject_id'      => $request->subproject_id,
            'vendor_id'          => $request->vendor_id,
            'po_id'              => $request->purchaseOrder,
            'instantdebitamount' => $request->instantDebitAmount,
            'file'               => $file_relative_path,
            'message'            => $request->message,
        ]);
        if ($insert_id) {
            DB::table('instant_debit_history')->insert([
                'user_id'            => $userId,
                'project_id'         => $request->project_id,
                'subproject_id'      => $request->subproject_id,
                'vendor_id'          => $request->vendor_id,
                'po_id'              => $request->purchaseOrder,
                'instantdebitamount' => $request->instantDebitAmount,
                'file'               => $file_relative_path,
                'message'            => $request->message,
                'instant_debit_id'   => $insert_id,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Submit successfully.');
    }
    // public function updateaddinstantDebitAmount(Request $request)
    // {
    //     $userId = session('user')->id;

    //     $instant_debitDetails = DB::table('instant_debit')->where('id', $request->id)->first();

    //     // Ensure $uploaded_file_name is always defined
    //     $uploaded_file_name = null;
    //     $file_original_name = null;

    //     if ($request->hasFile('file')) {
    //         $file               = $request->file('file');
    //         $file_original_name = $file->getClientOriginalName();
    //         // Define target path in public directory
    //         $destinationPath = public_path('user-uploads/instant_debit');
    //         if (! file_exists($destinationPath)) {
    //             mkdir($destinationPath, 0755, true);
    //         }
    //         $file->move($destinationPath, $file_original_name);
    //         $file_relative_path = 'user-uploads/instant_debit/' . $file_original_name;
    //     } else {
    //         $file_relative_path = $request->old_file;
    //     }

    //     DB::table('instant_debit')->where('id', $instant_debitDetails->id)
    //         ->update([
    //             'user_id'            => $userId,
    //             'instantdebitamount' => $request->instantDebitAmount,
    //             'file'               => $file_relative_path,
    //             'message'            => $request->message,
    //         ]);

    //     DB::table('instant_debit_history')->insert([
    //         'instant_debit_id'   => $instant_debitDetails->id,
    //         'user_id'            => $userId,
    //         'project_id'         => $instant_debitDetails->project_id,
    //         'subproject_id'      => $instant_debitDetails->subproject_id,
    //         'vendor_id'          => $instant_debitDetails->vendor_id,
    //         'po_id'              => $instant_debitDetails->po_id,
    //         'instantdebitamount' => $request->instantDebitAmount,
    //         'file'               => $file_relative_path,
    //         'message'            => $request->message,
    //     ]);

    //     return redirect()->route('dashboard')->with('success', 'Submit successfully.');
    // }
    
   public function updateaddinstantDebitAmount(Request $request)
    {
        $userId = session('user')->id;

        $instant_debitDetails = DB::table('instant_debit')->where('id', $request->id)->first();

        // Ensure $uploaded_file_name is always defined
        $uploaded_file_name = null;
        $file_original_name = null;

        if ($request->hasFile('file')) {
            $file               = $request->file('file');
            $file_original_name = $file->getClientOriginalName();
            // Define target path in public directory
            $destinationPath = public_path('user-uploads/instant_debit');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $file_original_name);
            $file_relative_path = 'user-uploads/instant_debit/' . $file_original_name;
        } else {
            $file_relative_path = $request->old_file;
        }

        DB::table('instant_debit')->where('id', $instant_debitDetails->id)
            ->update([
                'user_id'            => $userId,
                'instantdebitamount' => $request->instantDebitAmount,
                'file'               => $file_relative_path,
                'message'            => $request->message,
            ]);

        DB::table('instant_debit_history')->insert([
            'instant_debit_id'   => $instant_debitDetails->id,
            'user_id'            => $userId,
            'project_id'         => $instant_debitDetails->project_id,
            'subproject_id'      => $instant_debitDetails->subproject_id,
            'vendor_id'          => $instant_debitDetails->vendor_id,
            'po_id'              => $instant_debitDetails->po_id,
            'instantdebitamount' => $request->instantDebitAmount,
            'file'               => $file_relative_path,
            'message'            => $request->message,
        ]);

        if ($instant_debitDetails->bill_id) {

            $bill = DB::table('purchase_bills')->where('id', $instant_debitDetails->bill_id)->first();

            $totalInstaAmount = DB::table('instant_debit')
                ->where('bill_id', $instant_debitDetails->bill_id)
                ->sum('instantdebitamount');
            $totalInstaAmount = $totalInstaAmount ?? 0;

            $release_hold_amt_pay = DB::table('release_hold_amt')
                ->where('release_bill_id', $instant_debitDetails->bill_id)
                ->where('po_id', $bill->purchase_order_id)
                ->where('status', 2)
                ->sum('hold_amt');
            $release_hold_amt_pay = $release_hold_amt_pay ?? 0;

            $totalRetention = DB::table('purchase_bill_items')
                ->where('purchase_bills_id', $instant_debitDetails->bill_id)
                ->sum('retention_cost');
            $totalRetention = $totalRetention ?? 0;

            $netpayableamt = $bill->total - ($bill->HoldBillAmt + $bill->AdvanceRecovery + $bill->Steel + $bill->Cement + $bill->Electricity + $bill->OtherDebits + $bill->SafetyDebit + $bill->QualityDebit + $bill->Housekeeping + $bill->PoorPerformance + $totalInstaAmount + $totalRetention) + $release_hold_amt_pay;
            DB::table('purchase_bills')
                ->where('id', $instant_debitDetails->bill_id)
                ->update([
                    'instant_debit_amount' => $totalInstaAmount,
                    'netpayableamount'     => $netpayableamt,
                    'retention_cost_amt'       => $totalRetention,
                    'release_hold_amt_pay' => $release_hold_amt_pay,
                ]);

        };

        // same page
        // return redirect()->route()->with('success', 'Submit successfully.');
        return redirect()->back()->with('success', 'Submit successfully.');

    }
    
    public function getInstantDebitAmt(Request $request)
    {
        $userId = session('user')->id;

        $p_role = DB::table('priority_role')
            ->where([
                'project_id' => $request->project_id,
                'user_id'    => $userId,
            ])
            ->first();
        $p_role_count = DB::table('priority_role')
            ->where([
                'project_id' => $request->project_id,
            ])
            ->count();

        if (! $p_role) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You have not access to instant debit on this project.',
            ], 200);
        }

        $this->current_priority_id = $p_role->priority_id;

        $this->instant_debit = DB::table('instant_debit as d')
            ->join('projects as p', 'p.id', '=', 'd.project_id')
            ->join('tasks as t', 't.id', '=', 'd.subproject_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
            ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
            ->join('priority_role as pr', 'pr.user_id', '=', 'd.user_id')
            ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number', 'pr.priority_id')
            ->groupBy('d.id')
            ->where([
                'd.project_id'    => $request->project_id,
                'd.subproject_id' => $request->subproject_id,
                'd.vendor_id'     => $request->vendor_id,
                'd.po_id'         => $request->po_id,
                'd.bill_status'   => '1',
            ])->get();

        return response()->json([
            'current_priority_id' => $this->current_priority_id,
            'p_role_count'        => $p_role_count,
            'instant_debits'      => $this->instant_debit,
        ]);
    }

    public function getpurchaseOrder(Request $request)
    {

        $this->purchase_orders = DB::table('purchase_orders')->where([
            'project_id'    => $request->project_id,
            'subproject_id' => $request->subproject_id,
            'vendor_id'     => $request->vendor_id,
            'validate'      => true,
        ])->get();

        return response()->json($this->purchase_orders);
    }

    public function getInstantDebitDetails(Request $request)
    {
        $id = $request->input('id');

        $instant_debit = DB::table('instant_debit as d')
            ->join('projects as p', 'p.id', '=', 'd.project_id')
            ->join('tasks as t', 't.id', '=', 'd.subproject_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
            ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
            ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number')
            ->where('d.id', $id)->first();

        return response()->json($instant_debit);
    }

    public function deleteInstantDebitDetails(Request $request)
    {
        $userId = session('user')->id;
        $id     = $request->input('id');

        $instant_debitDetails = DB::table('instant_debit')->where('id', $id)->first();

        DB::table('instant_debit_history')->insert([
            'instant_debit_id'   => $id,
            'user_id'            => $userId,
            'project_id'         => $instant_debitDetails->project_id,
            'subproject_id'      => $instant_debitDetails->subproject_id,
            'vendor_id'          => $instant_debitDetails->vendor_id,
            'po_id'              => $instant_debitDetails->po_id,
            'instantdebitamount' => $instant_debitDetails->instantdebitamount,
            'file'               => $instant_debitDetails->file,
            'message'            => $instant_debitDetails->message,
            'status'             => '2',
            'created_at'         => now(),
        ]);

        DB::table('instant_debit')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Instant debit deleted successfully.']);
    }

    // public function getBillDetails($id)
    // {
    //     $bill            = PurchaseBill::findOrFail($id);
    //     $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();

    //     $userId = session('user')->id;

    //     $p_role = DB::table('priority_role')->where([
    //         'project_id' => $purchase_orders->project_id,
    //         'user_id'    => $userId,
    //     ])->first();

    //     if (! $p_role) {
    //         return redirect()->back()->with('error', 'Your role is not defined.');
    //     }

    //     $priority_id = $p_role->priority_id;

    //     $instant_debitDetails = DB::table('instant_debit')->where([
    //         'project_id'    => $purchase_orders->project_id,
    //         'subproject_id' => $purchase_orders->subproject_id,
    //         'vendor_id'     => $purchase_orders->vendor_id,
    //         'po_id'         => $purchase_orders->id,
    //         'bill_id'       => $bill->id,
    //         'status'        => '1',
    //     ])->first();

    //     $nes_p_role      = null;
    //     $nes_priority_id = null;

    //     if ($instant_debitDetails) {
    //         $nes_p_role = DB::table('priority_role')->where([
    //             'project_id' => $purchase_orders->project_id,
    //             'user_id'    => $instant_debitDetails->user_id,
    //         ])->first();

    //         $nes_priority_id = $nes_p_role ? $nes_p_role->priority_id : null;
    //     }

    //     $instant_debit_amount = $instant_debitDetails ? $instant_debitDetails->instantdebitamount : null;

    //     // Permission logic — block if current user has lower authority
    //     if (($priority_id > $nes_priority_id)) {
    //         $pr_status = '';

    //     } else {
    //         $pr_status = 'no_permission';
    //     }

    //     $debit_user_id = $bill->debit_user_id ?? null;
    //     if ($debit_user_id) {
    //         $debit_p_role = DB::table('priority_role')->where([
    //             'project_id' => $purchase_orders->project_id,
    //             'user_id'    => $debit_user_id,
    //         ])->first();

    //         if (($priority_id >= $debit_p_role->priority_id)) {
    //             $smtbtn_status = '';
    //         } else {
    //             $smtbtn_status = 'no_permission';
    //         }
    //     } else {
    //         $smtbtn_status = '';
    //     }

    //     // $all_hold = DB::table('purchase_bills')
    //     // ->where('purchase_order_id', $bill->purchase_order_id)->where('status','open')->where('id','>', $bill->id)
    //     // ->where('HoldBillAmt', '!=', null)->where('HoldBillAmt', '!=', 0)->where('id', '!=', $id)->select('id', 'HoldBillAmt')
    //     // ->get();

    //     $all_hold = DB::table('purchase_bills')
    //         ->where('purchase_order_id', $bill->purchase_order_id)
    //         ->where('status', 'open')
    //         ->where('id', '<', $bill->id)
    //         ->whereNotNull('HoldBillAmt')
    //         ->where('HoldBillAmt', '!=', 0)
    //         ->select('id', 'HoldBillAmt')
    //         ->get();

    //     $p_role_count = DB::table('priority_role')
    //         ->where([
    //             'project_id' => $purchase_orders->project_id,
    //         ])
    //         ->count();

    //     $current_priority_id = $p_role->priority_id;

    //     // $this->instant_debit = DB::table('instant_debit as d')
    //     //     ->join('projects as p', 'p.id', '=', 'd.project_id')
    //     //     ->join('tasks as t', 't.id', '=', 'd.subproject_id')
    //     //     ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
    //     //     ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
    //     //     ->join('priority_role as pr', 'pr.user_id', '=', 'd.user_id')
    //     //     ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number', 'pr.priority_id')
    //     //     ->where('bill_id', $bill->id)->where('bill_status', '2')->get();
        
    //      $this->instant_debit = DB::table('instant_debit as d')
    //         ->join('projects as p', 'p.id', '=', 'd.project_id')
    //         ->join('tasks as t', 't.id', '=', 'd.subproject_id')
    //         ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
    //         ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
    //         ->join('priority_role as pr', 'pr.user_id', '=', 'd.user_id')
    //         ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number', 'pr.priority_id')
    //         ->where('d.bill_id', $bill->id)
    //         ->where('d.bill_status', '2')
    //         ->whereColumn('d.project_id', 'pr.project_id')
    //         ->get();

    //     // print_r($all_hold );exit;

    //     return response()->json([
    //         'bill_id'             => $bill->id,
    //         'po_id'               => $purchase_orders->po_number,
    //         'priority_id'         => $priority_id,
    //         'nes_p_role'          => $nes_priority_id,
    //         'HoldBillAmt'         => $bill->HoldBillAmt,
    //         'AdvanceRecovery'     => $bill->AdvanceRecovery,
    //         'Steel'               => $bill->Steel,
    //         'Cement'              => $bill->Cement,
    //         'Electricity'         => $bill->Electricity,
    //         'OtherDebits'         => $bill->OtherDebits,
    //         'SafetyDebit'         => $bill->SafetyDebit,
    //         'QualityDebit'        => $bill->QualityDebit,
    //         'Housekeeping'        => $bill->Housekeeping,
    //         'PoorPerformance'     => $bill->PoorPerformance,
    //         'stageDescription'    => $bill->stageDescription,
    //         'billDescription'     => $bill->billDescription,
    //         // 'instantDebitAmount' => $instant_debit_amount,
    //         'pr_status'           => $pr_status,
    //         'smtbtn_status'       => $smtbtn_status,
    //         'all_hold'            => $all_hold,

    //         'current_priority_id' => $current_priority_id,
    //         'p_role_count'        => $p_role_count,
    //         'instant_debits'      => $this->instant_debit,
    //     ]);

    // }
       public function getBillDetails($id)
    {
        $bill = PurchaseBill::findOrFail($id);
        // print_r($bill);exit;
        $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();
        $userId          = session('user')->id;

        $p_role = DB::table('priority_role')->where([
            'project_id' => $purchase_orders->project_id,
            'user_id'    => $userId,
        ])->first();

        if (! $p_role) {
            return redirect()->back()->with('error', 'Your role is not defined.');
        }

        $priority_id = $p_role->priority_id;

        $instant_debitDetails = DB::table('instant_debit')->where([
            'project_id'    => $purchase_orders->project_id,
            'subproject_id' => $purchase_orders->subproject_id,
            'vendor_id'     => $purchase_orders->vendor_id,
            'po_id'         => $purchase_orders->id,
            'bill_id'       => $bill->id,
            'status'        => '1',
        ])->first();

        $nes_p_role      = null;
        $nes_priority_id = null;

        if ($instant_debitDetails) {
            $nes_p_role = DB::table('priority_role')->where([
                'project_id' => $purchase_orders->project_id,
                'user_id'    => $instant_debitDetails->user_id,
            ])->first();

            $nes_priority_id = $nes_p_role ? $nes_p_role->priority_id : null;
        }

        $instant_debit_amount = $instant_debitDetails ? $instant_debitDetails->instantdebitamount : null;

        // Permission logic — block if current user has lower authority
        if (($priority_id > $nes_priority_id)) {
            $pr_status = '';

        } else {
            $pr_status = 'no_permission';
        }

        $debit_user_id = $bill->debit_user_id ?? null;
        if ($debit_user_id) {
            $debit_p_role = DB::table('priority_role')->where([
                'project_id' => $purchase_orders->project_id,
                'user_id'    => $debit_user_id,
            ])->first();

            if (($priority_id >= $debit_p_role->priority_id)) {
                $smtbtn_status = '';
            } else {
                $smtbtn_status = 'no_permission';
            }
        } else {
            $smtbtn_status = '';
        }

        // $all_hold = DB::table('purchase_bills')
        // ->where('purchase_order_id', $bill->purchase_order_id)->where('status','open')->where('id','>', $bill->id)
        // ->where('HoldBillAmt', '!=', null)->where('HoldBillAmt', '!=', 0)->where('id', '!=', $id)->select('id', 'HoldBillAmt')
        // ->get();

        // $all_hold = DB::table('purchase_bills')
        //     ->where('purchase_order_id', $bill->purchase_order_id)
        //     ->where('status', 'open')
        //     ->where('id', '<', $bill->id)
        //     ->whereNotNull('HoldBillAmt')
        //     ->where('HoldBillAmt', '!=', 0)
        //     ->select('id', 'HoldBillAmt')
        //     ->get();

        // $all_hold = DB::table('release_hold_amt')->where('po_id', $bill->purchase_order_id)
        //     ->where('status', 1)
        //     ->where('bill_id', '!=', $id)
        //     ->where('bill_id', '>', $id)
        //     ->where('release_bill_id', null)
        //     ->select('bill_id as id', 'hold_amt as HoldBillAmt')
        //     ->get();

        // $all_hold = DB::table('release_hold_amt as r')->join('purchase_bills as pb', 'pb.id', '=', 'r.bill_id')
        //     ->where('r.po_id', $bill->purchase_order_id)
        //     ->where('r.status', 1)
        //     ->where('pb.status', 'open')
        //     ->where('r.bill_id', '<', $id)
        //     ->whereNull('r.release_bill_id')
        //     ->select('r.bill_id as id', 'r.hold_amt as HoldBillAmt')
        //     ->get();

        $all_hold = DB::table('release_hold_amt as r')
            ->join('purchase_bills as pb', 'pb.id', '=', 'r.bill_id')
            ->where('r.po_id', $bill->purchase_order_id)
            ->where('r.status', 1)
            ->where('pb.status', 'open')
            ->where('r.bill_id', '<', $id)
            ->whereNull('r.release_bill_id')
            ->select('r.bill_id as id', 'r.hold_amt as HoldBillAmt')
            ->get();

        // Count of priority roles for the project

        $p_role_count = DB::table('priority_role')
            ->where([
                'project_id' => $purchase_orders->project_id,
            ])
            ->count();

        $current_priority_id = $p_role->priority_id;

        // $this->instant_debit = DB::table('instant_debit as d')
        //     ->join('projects as p', 'p.id', '=', 'd.project_id')
        //     ->join('tasks as t', 't.id', '=', 'd.subproject_id')
        //     ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
        //     ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
        //     ->join('priority_role as pr', 'pr.user_id', '=', 'd.user_id')
        //     ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number', 'pr.priority_id')
        //     ->where('bill_id', $bill->id)->where('bill_status', '2')->get();

        $this->instant_debit = DB::table('instant_debit as d')
            ->join('projects as p', 'p.id', '=', 'd.project_id')
            ->join('tasks as t', 't.id', '=', 'd.subproject_id')
            ->join('purchase_vendors as v', 'v.id', '=', 'd.vendor_id')
            ->join('purchase_orders as po', 'po.id', '=', 'd.po_id')
            ->join('priority_role as pr', 'pr.user_id', '=', 'd.user_id')
            ->select('d.*', 'p.project_name', 't.task_short_code', 't.heading', 'v.primary_name as vendorname', 'po.po_number', 'pr.priority_id')
            ->where('d.bill_id', $bill->id)
            ->where('d.bill_status', '2')
            ->whereColumn('d.project_id', 'pr.project_id')
            ->get();

        // print_r($all_hold );exit;

        return response()->json([
            'bill_id'             => $bill->id,
            'po_id'               => $purchase_orders->po_number,
            'priority_id'         => $priority_id,
            'nes_p_role'          => $nes_priority_id,
            'HoldBillAmt'         => $bill->HoldBillAmt,
            'AdvanceRecovery'     => $bill->AdvanceRecovery,
            'Steel'               => $bill->Steel,
            'Cement'              => $bill->Cement,
            'Electricity'         => $bill->Electricity,
            'OtherDebits'         => $bill->OtherDebits,
            'SafetyDebit'         => $bill->SafetyDebit,
            'QualityDebit'        => $bill->QualityDebit,
            'Housekeeping'        => $bill->Housekeeping,
            'PoorPerformance'     => $bill->PoorPerformance,
            'stageDescription'    => $bill->stageDescription,
            'billDescription'     => $bill->billDescription,
            // 'instantDebitAmount' => $instant_debit_amount,
            'pr_status'           => $pr_status,
            'smtbtn_status'       => $smtbtn_status,
            'all_hold'            => $all_hold,

            'current_priority_id' => $current_priority_id,
            'p_role_count'        => $p_role_count,
            'instant_debits'      => $this->instant_debit,
        ]);

    }

    // public function update_debit_details(Request $request)
    // {
    //     // print_r($request->all());exit;

    //     $bill            = PurchaseBill::findOrFail($request->bill_id);
    //     $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();
    //     $userId          = session('user')->id;

    //     $holdValues = $request->input('hold_ids');
    //     if ($holdValues && is_array($holdValues)) {

    //         foreach ($holdValues as $id) {
    //             $holdAmt = DB::table('purchase_bills')->where('id', $id)->first();
    //             DB::table('release_hold_amt')->insert([
    //                 'release_bill_id' => $request->bill_id,
    //                 'bill_id'         => $id,
    //                 'po_id'           => $holdAmt->purchase_order_id,
    //                 'hold_amt'        => $holdAmt->HoldBillAmt,
    //             ]);
    //             DB::table('purchase_bills')->where('id', $id)->update([
    //                 'HoldBillAmt' => 0,
    //             ]);
    //         }
    //     }

    //     $p_role = DB::table('priority_role')->where([
    //         'project_id' => $purchase_orders->project_id,
    //         'user_id'    => $userId,
    //     ])->first();

    //     if (! $p_role) {
    //         return redirect()->back()->with('error', 'Your role is not defined.');
    //     }

    //     $priority_id = $p_role->priority_id;

    //     // Handle debit details
    //     $debit_exist = DB::table('debit_details')->where('bill_id', $request->bill_id)->where('status', 1)->first();

    //     if ($debit_exist) {
    //         $check_pr = DB::table('priority_role')->where([
    //             'project_id' => $purchase_orders->project_id,
    //             'user_id'    => $debit_exist->debit_user_id,
    //         ])->first();

    //         if ($check_pr && $check_pr->priority_id <= $priority_id) {
    //             DB::table('debit_details')->where('id', $debit_exist->id)->update([
    //                 'status' => 2,
    //             ]);
    //         }
    //     }

    //     $q = DB::table('purchase_bills')->where('id', $request->bill_id)->update([
    //         'debit_user_id'    => $userId,
    //         'HoldBillAmt'      => $request->HoldBillAmt,
    //         'AdvanceRecovery'  => $request->AdvanceRecovery,
    //         'Steel'            => $request->Steel,
    //         'Cement'           => $request->Cement,
    //         'Electricity'      => $request->Electricity,
    //         'OtherDebits'      => $request->OtherDebits,
    //         'SafetyDebit'      => $request->SafetyDebit,
    //         'QualityDebit'     => $request->QualityDebit,
    //         'Housekeeping'     => $request->Housekeeping,
    //         'PoorPerformance'  => $request->PoorPerformance,
    //         'stageDescription' => $request->stageDescription,
    //         'billDescription'  => $request->billDescription,
    //     ]);

    //     // Insert new debit record
    //     $history = DB::table('debit_details')->insert([
    //         'bill_id'           => $request->bill_id,
    //         'debit_user_id'     => $userId,
    //         'hold_bill'         => $request->HoldBillAmt,
    //         'advance_recovery'  => $request->AdvanceRecovery,
    //         'steel'             => $request->Steel,
    //         'cement'            => $request->Cement,
    //         'electricity'       => $request->Electricity,
    //         'other_debit'       => $request->OtherDebits,
    //         'safety'            => $request->SafetyDebit,
    //         'quality'           => $request->QualityDebit,
    //         'housekeeping'      => $request->Housekeeping,
    //         'poor_performance'  => $request->PoorPerformance,
    //         'stage_description' => $request->stageDescription,
    //         'bill_description'  => $request->billDescription,
    //     ]);

    //     return redirect()->route('bills.bill-approval')->with('success', 'Submit successfully.');
    // }
 public function update_debit_details(Request $request)
    {
        // print_r($request->all());exit;

        $bill            = PurchaseBill::findOrFail($request->bill_id);
        $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();
        $userId          = session('user')->id;
        
        DB::table('release_hold_amt')->updateOrInsert(
            [
                'bill_id' => $request->bill_id,
                'po_id'   => $purchase_orders->id,
            ],
            [
                'hold_amt' => $request->HoldBillAmt,
            ]
        );

        $holdValues = $request->input('hold_ids');
        if ($holdValues && is_array($holdValues)) {

            foreach ($holdValues as $id) {
                $holdAmt = DB::table('purchase_bills')->where('id', $id)->first();
                DB::table('release_hold_amt')->where('bill_id', $holdAmt->id)->where('po_id', $holdAmt->purchase_order_id)->update([
                    'release_bill_id' => $request->bill_id,
                    'status'          => 2,
                ]);

            }
        }

        $p_role = DB::table('priority_role')->where([
            'project_id' => $purchase_orders->project_id,
            'user_id'    => $userId,
        ])->first();

        if (! $p_role) {
            return redirect()->back()->with('error', 'Your role is not defined.');
        }

        $priority_id = $p_role->priority_id;

        // Handle debit details
        $debit_exist = DB::table('debit_details')->where('bill_id', $request->bill_id)->where('status', 1)->first();

        if ($debit_exist) {
            $check_pr = DB::table('priority_role')->where([
                'project_id' => $purchase_orders->project_id,
                'user_id'    => $debit_exist->debit_user_id,
            ])->first();

            if ($check_pr && $check_pr->priority_id <= $priority_id) {
                DB::table('debit_details')->where('id', $debit_exist->id)->update([
                    'status' => 2,
                ]);
            }
        }

        $release_hold_amt_pay = DB::table('release_hold_amt')
            ->where('release_bill_id', $request->bill_id)
            ->where('po_id', $purchase_orders->id)
            ->where('status', 2)
            ->sum('hold_amt');
        $release_hold_amt_pay = $release_hold_amt_pay ?? 0;
        // print_r($release_hold_amt_pay);exit;

        $totalInstaAmount = DB::table('instant_debit')
            ->where('bill_id', $request->bill_id)
            ->sum('instantdebitamount');
        $totalInstaAmount = $totalInstaAmount ?? 0;
        $totalRetention   = DB::table('purchase_bill_items')
            ->where('purchase_bills_id', $request->bill_id)
            ->sum('retention_cost');
        $totalRetention = $totalRetention ?? 0;
        $netpayableamt  = $bill->total - ($request->HoldBillAmt + $request->AdvanceRecovery + $request->Steel + $request->Cement + $request->Electricity + $request->OtherDebits + $request->SafetyDebit + $request->QualityDebit + $request->Housekeeping + $request->PoorPerformance + $totalInstaAmount + $totalRetention) + $release_hold_amt_pay;

        // print_r($netpayableamt);exit;

        $q = DB::table('purchase_bills')->where('id', $request->bill_id)->update([
            'debit_user_id'        => $userId,
            'HoldBillAmt'          => $request->HoldBillAmt,
            'AdvanceRecovery'      => $request->AdvanceRecovery,
            'Steel'                => $request->Steel,
            'Cement'               => $request->Cement,
            'Electricity'          => $request->Electricity,
            'OtherDebits'          => $request->OtherDebits,
            'SafetyDebit'          => $request->SafetyDebit,
            'QualityDebit'         => $request->QualityDebit,
            'Housekeeping'         => $request->Housekeeping,
            'PoorPerformance'      => $request->PoorPerformance,
            'stageDescription'     => $request->stageDescription,
            'billDescription'      => $request->billDescription,
            'netpayableamount'     => $netpayableamt,
            'instant_debit_amount' => $totalInstaAmount,
            'release_hold_amt_pay' => $release_hold_amt_pay,
            'retention_cost_amt'       => $totalRetention,
        ]);

        // Insert new debit record
        $history = DB::table('debit_details')->insert([
            'bill_id'           => $request->bill_id,
            'debit_user_id'     => $userId,
            'hold_bill'         => $request->HoldBillAmt,
            'advance_recovery'  => $request->AdvanceRecovery,
            'steel'             => $request->Steel,
            'cement'            => $request->Cement,
            'electricity'       => $request->Electricity,
            'other_debit'       => $request->OtherDebits,
            'safety'            => $request->SafetyDebit,
            'quality'           => $request->QualityDebit,
            'housekeeping'      => $request->Housekeeping,
            'poor_performance'  => $request->PoorPerformance,
            'stage_description' => $request->stageDescription,
            'bill_description'  => $request->billDescription,
        ]);

        return redirect()->route('bills.bill-approval')->with('success', 'Submit successfully.');
    }
    

    //  public function update_debit_details_by_accountant(Request $request)
    // {

    //     // print_r($request->all());exit;

    //     $userId = session('user')->id;

    //     $totalInstaAmount = DB::table('instant_debit')
    //         ->where('bill_id', $request->bill_id)
    //         ->sum('instantdebitamount');
    //     $totalInstaAmount = $totalInstaAmount ?? 0;
    //     $netpayableamt    = $bill->total - ($request->holdbillamt_input + $request->advancerecovery_input + $request->steel_input + $request->cement_input + $request->electricity_input + $request->otherdebit_input + $request->safetydebit_input + $request->qualitydebit_input + $request->housekeeping_input + $request->poorperformance_input + $totalInstaAmount);
    //     // Update purchase_bills
    //     DB::table('purchase_bills')->where('id', $request->bill_id)->update([
    //         'debit_user_id'    => $userId,
    //         'HoldBillAmt'      => $request->holdbillamt_input,
    //         'AdvanceRecovery'  => $request->advancerecovery_input,
    //         'Steel'            => $request->steel_input,
    //         'Cement'           => $request->cement_input,
    //         'Electricity'      => $request->electricity_input,
    //         'OtherDebits'      => $request->otherdebit_input,
    //         'SafetyDebit'      => $request->safetydebit_input,
    //         'QualityDebit'     => $request->qualitydebit_input,
    //         'Housekeeping'     => $request->housekeeping_input,
    //         'PoorPerformance'  => $request->poorperformance_input,
    //         'netpayableamount' => $netpayableamt,
    //     ]);

    //     // Insert into debit_details history
    //     DB::table('debit_details')->insert([
    //         'bill_id'           => $request->bill_id,
    //         'debit_user_id'     => $userId,
    //         'hold_bill'         => $request->holdbillamt_input,
    //         'advance_recovery'  => $request->advancerecovery_input,
    //         'steel'             => $request->steel_input,
    //         'cement'            => $request->cement_input,
    //         'electricity'       => $request->electricity_input,
    //         'other_debit'       => $request->otherdebit_input,
    //         'safety'            => $request->safetydebit_input,
    //         'quality'           => $request->qualitydebit_input,
    //         'housekeeping'      => $request->housekeeping_input,
    //         'poor_performance'  => $request->poorperformance_input,
    //         'stage_description' => "Accountant Update",
    //         'bill_description'  => "Accountant Update",
    //         'updated_at'        => now(),
    //     ]);
    //     return redirect()->route('vendor-payments.create', ['bill' => $request->bill_id])->with('success', 'Update successfully.');

    // }

    public function update_debit_details_by_accountant(Request $request)
    {

        // print_r($request->all());exit;
         $bill            = PurchaseBill::findOrFail($request->bill_id);
        $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();
        // $userId          = session('user')->id;

        $userId = session('user')->id;
         $release_hold_amt_pay = DB::table('release_hold_amt')
            ->where('release_bill_id', $request->bill_id)
            ->where('po_id', $purchase_orders->id)
            ->where('status', 2)
            ->sum('hold_amt');
        $release_hold_amt_pay = $release_hold_amt_pay ?? 0;
        // print_r($release_hold_amt_pay);exit;

        $totalInstaAmount = DB::table('instant_debit')
            ->where('bill_id', $request->bill_id)
            ->sum('instantdebitamount');
        $totalInstaAmount = $totalInstaAmount ?? 0;
        $totalRetention   = DB::table('purchase_bill_items')
            ->where('purchase_bills_id', $request->bill_id)
            ->sum('retention_cost');
        $totalRetention = $totalRetention ?? 0;

       $netpayableamt    = $bill->total - ($request->holdbillamt_input + $request->advancerecovery_input + $request->steel_input + $request->cement_input + $request->electricity_input + $request->otherdebit_input + $request->safetydebit_input + $request->qualitydebit_input + $request->housekeeping_input + $request->poorperformance_input + $totalInstaAmount + $totalRetention) + $release_hold_amt_pay;
        // Update purchase_bills
        DB::table('purchase_bills')->where('id', $request->bill_id)->update([
            'debit_user_id'    => $userId,
            'HoldBillAmt'      => $request->holdbillamt_input,
            'AdvanceRecovery'  => $request->advancerecovery_input,
            'Steel'            => $request->steel_input,
            'Cement'           => $request->cement_input,
            'Electricity'      => $request->electricity_input,
            'OtherDebits'      => $request->otherdebit_input,
            'SafetyDebit'      => $request->safetydebit_input,
            'QualityDebit'     => $request->qualitydebit_input,
            'Housekeeping'     => $request->housekeeping_input,
            'PoorPerformance'  => $request->poorperformance_input,
            'netpayableamount' => $netpayableamt,
        ]);

        // Insert into debit_details history
        DB::table('debit_details')->insert([
            'bill_id'           => $request->bill_id,
            'debit_user_id'     => $userId,
            'hold_bill'         => $request->holdbillamt_input,
            'advance_recovery'  => $request->advancerecovery_input,
            'steel'             => $request->steel_input,
            'cement'            => $request->cement_input,
            'electricity'       => $request->electricity_input,
            'other_debit'       => $request->otherdebit_input,
            'safety'            => $request->safetydebit_input,
            'quality'           => $request->qualitydebit_input,
            'housekeeping'      => $request->housekeeping_input,
            'poor_performance'  => $request->poorperformance_input,
            'stage_description' => "Accountant Update",
            'bill_description'  => "Accountant Update",
            'updated_at'        => now(),
        ]);
        return redirect()->route('vendor-payments.create', ['bill' => $request->bill_id])->with('success', 'Update successfully.');

    }
    public function update_status_bill(Request $request)
    {

        date_default_timezone_set("Asia/Kolkata");
        if (! $this->validateRequest($request)) {
            return response()->json(['success' => false, 'message' => 'Missing required parameters']);
        }

        try {
            $fileData    = $this->handleFileUpload($request);
            $invoiceItem = $this->getInvoiceItem($request->row_id);
            if (! $invoiceItem) {
                return $this->errorResponse('Bill item not found.');
            }
            $data = $this->initializeCommonData($request, $invoiceItem, $fileData);
            //   print_r( $data->all());exit;
            if ($request->approveReject == 2) {
                return $this->handleApproval($data);
            } else {
                return $this->handleRejection($data);
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Operation failed: ' . $e->getMessage());
        }
    }

    private function validateRequest(Request $request): bool
    {
        return $request->has(['row_id', 'approveReject', 'approve_status', 'project_id', 'priority_count']);
    }

    private function handleFileUpload(Request $request): array
    {
        if (! $request->hasFile('file')) {
            return ['uploaded_file_name' => null, 'file_original_name' => null];
        }

        $file = $request->file('file');
        return [
            'uploaded_file_name' => $file->store('bill-approval-files', 'public'),
            'file_original_name' => $file->getClientOriginalName(),
        ];
    }

    private function getInvoiceItem($id)
    {
        return DB::table('approval_bills_by')->where('id', $id)->first();
    }

    private function initializeCommonData(Request $request, $invoiceItem, array $fileData): array
    {
        $sessionUser  = session('user');
        $p_role_query = DB::table('priority_role')
            ->where('project_id', $request->project_id)
            ->where('user_id', $sessionUser->id);
        $p_role = $p_role_query->first();
        $this->updateUnavailableApprovers($invoiceItem->bill_id, $request->project_id, $p_role, $request->row_id);

        return [
            'request'      => $request,
            'invoiceItem'  => $invoiceItem,
            'fileData'     => $fileData,
            'sessionUser'  => $sessionUser,
            'p_role'       => $p_role,
            'bill_item_id' => $invoiceItem->bill_id,
            'project_id'   => $request->project_id,
            'reference_no' => $request->reference_no,
        ];
    }

    private function updateUnavailableApprovers($billId, $projectId, $currentPriorityRole, $id)
    {
        $currentBill          = DB::table('approval_bills_by')->where('id', $id)->first();
        $currentUserPeriority = $currentPriorityRole->priority_id;
        $currentBillPriority  = $currentBill->approval_status;

        $difference = abs($currentBillPriority - $currentUserPeriority);
        $updateData = [
            'approval_status' => $currentPriorityRole->priority_id,
            'message'         => 'Approver Is Not Available',
            'updated_at'      => now(),
        ];

        if ($currentPriorityRole->priority_id > 1) {
            if ($difference >= 1) {
                if ($currentBillPriority < $currentUserPeriority) {
                    $i = $currentBillPriority;
                    for ($i; $i < $currentUserPeriority; $i++) {
                        $pre_p_role_query = DB::table('priority_role')
                            ->where('project_id', $projectId)
                            ->where('priority_id', $i);
                        $pre_p_role = $pre_p_role_query->first();
                        if ($pre_p_role) {
                            $insertData = [
                                'bill_id' => $billId,
                                'user_id' => $pre_p_role->user_id,
                                'bypass'  => 'bypass',
                                'message' => request('message_appnotavailable') ?? 'Approver Is Not Available',
                            ];
                            DB::table('approval_bill_details')->insert($insertData);
                        }
                    }
                }
            }
        }
    }

    private function handleApproval(array $data)
    {

        $request      = $data['request'];
        $invoiceItem  = $data['invoiceItem'];
        $fileData     = $data['fileData'];
        $sessionUser  = $data['sessionUser'];
        $p_role       = $data['p_role'];
        $billId       = $data['bill_item_id'];
        $projectId    = $data['project_id'];
        $reference_no = $data['reference_no'];

        $updateData = [
            'message'            => $request->message,
            'actual_status'      => 2,
            'uploaded_file_name' => $fileData['uploaded_file_name'],
            'file_original_name' => $fileData['file_original_name'],
            'updated_at'         => now(),
        ];

        $previousStatus = $invoiceItem->approval_status;
        $priorityCount  = $request->priority_count;

        $query = DB::table('priority_role')
            ->where('project_id', $projectId)
            ->where('user_id', $sessionUser->id);

        $priority = $query->first();

        if ($priority) {
            $priorityID = $priority->priority_id;
        }

        if ($previousStatus == $priorityCount || $priorityID == $priorityCount) {
            // Final approval
            $updateData['status']          = 2;
            $updateData['approval_status'] = $priorityCount;
            DB::table('approval_bills_by')->where('id', $invoiceItem->id)->update($updateData);

            $this->insertApprovalDetail($billId, $sessionUser->id, 2, $request->message,
                $request->document_name, $fileData);

            DB::table('purchase_bills')->where('id', $billId)->update([
                'status'       => 'open',
                'updated_at'   => now(),
                'reference_no' => $reference_no,
            ]);

            $this->sendFinalApprovalEmails($billId, $projectId, $previousStatus);

        } else {
            // Intermediate approval
            // $newStatus = $previousStatus + 1;
            // priorityID
            $newStatus                     = $priorityID + 1;
            $updateData['approval_status'] = $newStatus;
            DB::table('approval_bills_by')->where('id', $invoiceItem->id)->update($updateData);

            // Handle previous approver if exists
            $this->handlePreviousApprover($billId, $projectId, $previousStatus, $request, $priorityID);
            $approvalStatus = 2;
            $this->insertApprovalDetail($billId, $sessionUser->id, $approvalStatus, $request->message,
                $request->document_name, $fileData);

            // Assign to next approver
            $nextApprover = DB::table('priority_role')
                ->where('project_id', $projectId)
                ->where('priority_id', $newStatus)
                ->first();
            // if ($nextApprover) {
            //     DB::table('approval_bill_details')->insert([
            //         'user_id' => $nextApprover->user_id,
            //         'bill_id' => $billId,
            //         'message' => 'Re Assigned',
            //         'created_at' => now(),
            //     ]);
            // }
            DB::table('purchase_bills')->where('id', $billId)->update([
                'updated_at'   => now(),
                'reference_no' => $reference_no,
            ]);

            $this->sendIntermediateApprovalEmails($billId, $projectId, $previousStatus, $newStatus);
        }
        return $this->successResponse(
            'Bill item approved successfully.',
            $this->getCurrentApprover($projectId, $newStatus ?? $priorityCount)
        );
    }

    private function handleRejection(array $data)
    {
        $request      = $data['request'];
        $invoiceItem  = $data['invoiceItem'];
        $fileData     = $data['fileData'];
        $sessionUser  = $data['sessionUser'];
        $p_role       = $data['p_role'];
        $billId       = $data['bill_item_id'];
        $projectId    = $data['project_id'];
        $reference_no = $data['reference_no'];

        $currentPriority = $p_role->priority_id;
        // $newStatus       = ($currentPriority > 1) ? $currentPriority - 1 : $currentPriority;
         $newStatus       = 1;

        $updateData = [
            'approval_status'    => $newStatus,
            'message'            => $request->message,
            'actual_status'      => 3,
            'uploaded_file_name' => $fileData['uploaded_file_name'],
            'file_original_name' => $fileData['file_original_name'],
            'updated_at'         => now(),
        ];

        // Perform update
        $result = DB::table('approval_bills_by')
            ->where('id', $invoiceItem->id)
            ->update($updateData);

        $this->insertApprovalDetail($billId, $sessionUser->id, 3, $request->message,
            $request->document_name, $fileData);

        // Assign to previous approver
        $previousApprover = DB::table('priority_role')
            ->where('project_id', $projectId)
            ->where('priority_id', $newStatus)
            ->first();

        if ($previousApprover) {
            DB::table('approval_bill_details')->insert([
                'user_id'    => $previousApprover->user_id,
                'bill_id'    => $billId,
                'message'    => 'Re Assigned',
                'created_at' => now(),
            ]);
        }

        $this->sendRejectionEmails($billId, $projectId, $currentPriority, $newStatus, $request->message);

        if ($newStatus == $request->priority_count) {
            DB::table('approval_bills_by')->where('id', $invoiceItem->id)->update([
                'status'     => 2,
                'updated_at' => now(),
            ]);

            $this->sendFinalRejectionEmails($billId, $projectId, $request->message);
        }
        DB::table('purchase_bills')->where('id', $billId)->update([
            'updated_at'   => now(),
            'reference_no' => $reference_no,
        ]);
        return $this->successResponse('Bill item rejected successfully.', $this->getCurrentApprover($projectId, $newStatus));
    }

    private function insertApprovalDetail($billId, $userId, $status, $message, $documentName, $fileData)
    {
        DB::table('approval_bill_details')->insert([
            'user_id'            => $userId,
            'bill_id'            => $billId,
            'approval_status'    => $status,
            'message'            => $message,
            'document_name'      => $documentName,
            'uploaded_file_name' => $fileData['uploaded_file_name'],
            'file_original_name' => $fileData['file_original_name'],
            'created_at'         => now(),
        ]);
    }

    private function handlePreviousApprover($billId, $projectId, $previousStatus, $request, $priorityID)
    {
        $previousApprover = DB::table('priority_role')
            ->where('project_id', $projectId)
            ->where('priority_id', $previousStatus - 1)
            ->first();

        $currentUserPeriority    = $priorityID;
        $currentPreviousPriority = $previousStatus - 1;

        if ($previousApprover) {
            $existing = DB::table('approval_bill_details')
                ->where('user_id', $previousApprover->user_id)
                ->where('bill_id', $billId)
                ->orderByDesc('id')
                ->first();

            if ($existing && $existing->approval_status === null) {
                DB::table('approval_bill_details')->insert([
                    'bill_id' => $billId,
                    'user_id' => $previousApprover->user_id,
                    'bypass'  => 'bypass',
                    'message' => $request->message_appnotavailable ?? 'Approver Is Not Available',
                ]);
            }
        }
    }

    private function sendFinalApprovalEmails($billId, $projectId, $approverPriority)
    {
        $billDetails = $this->getBillDetailss($billId);
        // $approver = $this->getPriorityUser($projectId, $approverPriority);

        $currentUserId = Auth::id();
        $approver      = $this->getPriorityUserPrevious($projectId, $currentUserId);

        $vendorDetail = $this->getVendorDetailss($billDetails->purchase_vendor_id);

        $dynamicData = $this->prepareDynamicData($billDetails, $approver, $vendorDetail);

        $template = $this->getEmailTemplate('bill_approve_by_final_employee_for_vendor');
        if (! $template) {
            throw new \Exception('Email template not found');
        }

        $this->sendEmail(
            $vendorDetail->email,
            $template->subject,
            $template->template,
            $dynamicData
        );
        
        $approver = auth()->user();
        $notifyData = [
            'bill_id'     => $billDetails->id,
            'bill_no'     => $billDetails->purchase_bill_number,
            'total'       => $billDetails->total,
            'approved_by' => $approver->name,
                    'title'       => 'Bill Approved',
            'message'     => 'Bill #' . $billDetails->purchase_bill_number . ' approved by ' . $approver->name,
        ];

        $creator = User::find($vendorDetail->id);
        if ($creator) {
            $creator->notify(new BillApproveByFinalAuthority($notifyData));
        }
    }

    private function sendIntermediateApprovalEmails($billId, $projectId, $previousPriority, $newPriority)
    {
        $billDetails      = $this->getBillDetailss($billId);
        $currentApprover  = $this->getPriorityUser($projectId, $newPriority);
        $currentUserId    = Auth::id();
        $previousApprover = $this->getPriorityUserPrevious($projectId, $currentUserId);
        $vendorDetail     = $this->getVendorDetailss($billDetails->purchase_vendor_id);

        $dynamicData = $this->prepareDynamicData(
            $billDetails,
            $previousApprover,
            $vendorDetail,
            ['employee_name_nextpriority' => $currentApprover->name]
        );

        // Send to next approver
        $employeeTemplate = $this->getEmailTemplate('bill_approve_by_employee');
        $approver         = auth()->user();
        $notifyData       = [
            'bill_id'     => $billDetails->id,
            'bill_no'     => $billDetails->purchase_bill_number,
            'total'       => $billDetails->total,
            'approved_by' => $approver->name,
                    'title'       => 'Bill Approved',
            'message'     => 'Bill #' . $billDetails->purchase_bill_number . ' approved by ' . $approver->name,
        ];

        if ($employeeTemplate) {
            $this->sendEmail(
                $currentApprover->email,
                $employeeTemplate->subject,
                $employeeTemplate->template,
                $dynamicData
            );

            $creator = User::find($currentApprover->id);
            if ($creator) {
                $creator->notify(new BillApproveByFinalAuthority($notifyData));
            }
        }

        // Send to vendor
        $vendorTemplate = $this->getEmailTemplate('bill_approve_by_employee_for_vendor');
        if ($vendorTemplate) {
            $this->sendEmail(
                $vendorDetail->email,
                $vendorTemplate->subject,
                $vendorTemplate->template,
                $dynamicData
            );

            $creator = User::find($vendorDetail->id);
            if ($creator) {
                $creator->notify(new BillApproveByFinalAuthority($notifyData));
            }
        }

    }

    private function sendRejectionEmails($billId, $projectId, $rejecterPriority, $newPriority, $rejectionNote)
    {

        $billDetails = $this->getBillDetailss($billId);
        // $rejecter = $this->getPriorityUser($projectId, $rejecterPriority);
        $currentUserId = Auth::id();
        $rejecter      = $this->getPriorityUserPrevious($projectId, $currentUserId);
        $newApprover   = $this->getPriorityUser($projectId, $newPriority);
        $vendorDetail  = $this->getVendorDetailss($billDetails->purchase_vendor_id);

        $dynamicData = $this->prepareDynamicData(
            $billDetails,
            $newApprover,
            $vendorDetail,
            [
                'rejected_by_employee_name' => $rejecter->name,
                'rejection_note'            => $rejectionNote,
            ]
        );

         $approver         = auth()->user();
        $notifyData       = [
            'bill_id'     => $billDetails->id,
            'bill_no'     => $billDetails->purchase_bill_number,
            'total'       => $billDetails->total,
            'approved_by' => $approver->name,
                    'title'       => 'Bill Rejected',
            'message'     => 'Bill #' . $billDetails->purchase_bill_number . ' Rejected by ' . $approver->name,
        ];

        // Send to previous approver
        $employeeTemplate = $this->getEmailTemplate('bill_reject_by_employee');
        if ($employeeTemplate) {
            //  print_r($newApprover->id);exit;
            $this->sendEmail(
                $newApprover->email,
                $employeeTemplate->subject,
                $employeeTemplate->template,
                $dynamicData
            );
            $creator = User::find($newApprover->id);
            if ($creator) {
                $creator->notify(new BillApproveByFinalAuthority($notifyData));
            }
        }

        // Send to vendor
        $vendorTemplate = $this->getEmailTemplate('bill_rejected_by_employee_for_vendor');
        if ($vendorTemplate) {
            $this->sendEmail(
                $vendorDetail->email,
                $vendorTemplate->subject,
                $vendorTemplate->template,
                $dynamicData
            );
            $creator = User::find($vendorDetail->id);
            if ($creator) {
                $creator->notify(new BillApproveByFinalAuthority($notifyData));
            }
        }

    }

    private function sendFinalRejectionEmails($billId, $projectId, $rejectionNote)
    {
        // print_r($billId);exit;
        $billDetails  = $this->getBillDetailss($billId);
        $sessionUser  = session('user');
        $vendorDetail = $this->getVendorDetailss($billDetails->purchase_vendor_id);

        $dynamicData = $this->prepareDynamicData(
            $billDetails,
            null,
            $vendorDetail,
            [
                'rejected_by_employee_name' => $sessionUser->name,
                'rejection_note'            => $rejectionNote,
            ]
        );
                   $approver         = auth()->user();
        $notifyData       = [
            'bill_id'     => $billDetails->id,
            'bill_no'     => $billDetails->purchase_bill_number,
            'total'       => $billDetails->total,
            'approved_by' => $approver->name,
                    'title'       => 'Bill Rejected',
            'message'     => 'Bill #' . $billDetails->purchase_bill_number . 'final Rejected by ' . $approver->name,
        ];

        $vendorTemplate = $this->getEmailTemplate('bill_rejected_by_employee_for_vendor');
        if ($vendorTemplate) {
            $this->sendEmail(
                $vendorDetail->email,
                $vendorTemplate->subject,
                $vendorTemplate->template,
                $dynamicData
            );

             $creator = User::find($vendorDetail->id);
            if ($creator) {
                $creator->notify(new BillApproveByFinalAuthority($notifyData));
            }
        }
    }

    private function getBillDetailss($billId)
    {
        $bill = DB::table('purchase_bills')->where('id', $billId)->first();
        if (! $bill) {
            throw new \Exception('Bill not found');
        }
        return $bill;
    }

    private function getPriorityUser($projectId, $priority)
    {
        $role = DB::table('priority_role')
            ->where('project_id', $projectId)
            ->where('priority_id', $priority)
            ->first();

        if (! $role) {
            throw new \Exception("Priority user not found for priority $priority");
        }

        $user = DB::table('users')->where('id', $role->user_id)->first();
        if (! $user) {
            throw new \Exception("User not found for priority role $priority");
        }

        return $user;
    }
    private function getPriorityUserPrevious($projectId, $currentUserId)
    {

        $user          = Auth::user(); // gets the currently authenticated user
        $currentUserId = Auth::id();
        if (! $user) {
            throw new \Exception("No user is logged in.");
        }
        $user = DB::table('users')->where('id', $currentUserId)->first();
        if (! $user) {
            throw new \Exception("User not found for priority role $priority");
        }

        return $user;
    }

    private function getVendorDetailss($vendorId)
    {
        $vendor = DB::table('purchase_vendors')->where('id', $vendorId)->first();
        if (! $vendor) {
            throw new \Exception('Vendor not found');
        }

        $user = DB::table('users')->where('id', $vendor->user_id)->first();
        if (! $user) {
            throw new \Exception('Vendor user not found');
        }

        return $user;
    }

    private function prepareDynamicData($billDetails, $approver = null, $vendorDetail, $additionalData = [])
    {
        $poDetails  = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
        $project    = DB::table('projects')->where('id', $poDetails->project_id)->first();
        $subProject = DB::table('tasks')->where('id', $poDetails->subproject_id)->first();

        $baseData = [
            'approve_by_employee_name' => $approver ? $approver->name : null,
            'bill_no'                  => 'Bill#' . str_pad($billDetails->id, 3, '0', STR_PAD_LEFT),
            'bill_date'                => $billDetails->bill_date,
            'vendor_name'              => $vendorDetail->name,
            'sub_total'                => $billDetails->sub_total,
            'tax_total'                => $billDetails->tax_total,
            'total'                    => $billDetails->total,
            'project_name'             => $project ? $project->project_name : null,
            'subproject_name'          => $subProject ? $subProject->heading . ' ' . $subProject->task_short_code : null,
        ];

        return array_merge($baseData, $additionalData);
    }

    private function getEmailTemplate($templateKey)
    {
        return DB::table('email_templates')->where('template_key', $templateKey)->first();
    }

    private function sendEmail($toEmail, $subject, $template, $dynamicData)
    {
        if (empty($toEmail)) {
            return;
        }

        foreach ($dynamicData as $key => $value) {
            $subject  = str_replace("[$key]", $value, $subject);
            $template = str_replace("[$key]", $value, $template);
        }

        $emailData = [
            'subject' => $subject,
            'message' => $template,
        ];

        Mail::to($toEmail)->send(new DynamicEmail($emailData));

        // Notification::route('mail', $toEmail)
        //     ->notify(new SendInvoiceByContractor($emailData));
    }

    private function getCurrentApprover($projectId, $priority)
    {
        return DB::table('priority_role')
            ->where('project_id', $projectId)
            ->where('priority_id', $priority)
            ->value('user_id');
    }

    private function successResponse($message, $employeeId = null)
    {
        return response()->json([
            'success'     => true,
            'message'     => $message,
            'employee_id' => $employeeId,
        ]);
    }

    private function errorResponse($message)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ]);
    }

    public function getDebitDetails($billId)
    {
        $debit = DB::table('debit_details')->where('bill_id', $billId)->latest('id')->first();

        if ($debit) {
            return response()->json([
                'status' => true,
                'data'   => $debit,
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'No record found',
        ]);
    }
    
 public function update_status_pending(Request $request)
    {
        $bill = DB::table('purchase_bills')->where('id', $request->bill_id)->first();
        if ($bill) {
            DB::table('purchase_bills')->where('id', $request->bill_id)->update([
                'reference_bill_no' => $request->invoiceno,
                'status'            => 'pending',
                'updated_at'        => now(),
                'date_for_delay_count' => now(),
            ]);

            $purchase_orders = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();
            $projectId       = $purchase_orders->project_id;
            $user_pr         = DB::table('priority_role')->where('project_id', $projectId)->where('priority_id', 1)->first();

            $user       = DB::table('users')->where('id', $user_pr->user_id)->first();
            $vendor     = DB::table('purchase_vendors')->where('id', $bill->purchase_vendor_id)->first();
            $vendorUser = DB::table('users')->where('id', $vendor->user_id)->first();

            $templateData = DB::table('email_templates')->where('template_key', 'invoice_send_by_vendor')->first();

            if (! $templateData) {
                throw new \Exception('Email template not found.');
            }

            $dynamicData = [
                'employee_name' => $user->name,
                'bill_no'       => 'Bill#' . str_pad($bill->id, 3, '0', STR_PAD_LEFT),
                'bill_date'     => $bill->bill_date,
                'vendor_name'   => $vendorUser->name ?? '',
                'sub_total'     => $bill->sub_total,
                'tax_total'     => $bill->tax_total,
                'total'         => $bill->total,
                'invoice_no'    => $bill->reference_bill_no,
            ];

            $subject  = $templateData->subject;
            $template = $templateData->template;
            foreach ($dynamicData as $key => $value) {
                $subject  = str_replace("[$key]", $value, $subject);
                $template = str_replace("[$key]", $value, $template);
            }

            $emailData = [
                'subject' => $subject,
                'message' => $template,
            ];

            if (! empty($user->email)) {
                Mail::to($user->email)->send(new DynamicEmail($emailData));

            }

            return response()->json(['success' => true, 'message' => 'Bill status updated to pending successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Bill not found.']);
        }
    }
    
   
      public function update_reject_status_abstract(Request $request)
    {
        $bill = DB::table('purchase_bills')->where('id', $request->bill_id)->first();
        if ($bill) {
            DB::table('purchase_bills')->where('id', $request->bill_id)->update([
                'status'     => 'abstract_reject',
                'updated_at' => now(),
            ]);

            $user_id = session('user')->id;
            $po      = DB::table('purchase_orders')->where('id', $bill->purchase_order_id)->first();

            if (! $po) {
                return response()->json(['success' => false, 'message' => 'Purchase order not found.']);
            }
            $projectId     = $po->project_id;
            $priority_role = DB::table('priority_role')
                ->where('project_id', $projectId)
                ->where('user_id', $user_id)
                ->first();

            DB::table('abstract_reject_history')->insert([
                'bill_id'       => $request->bill_id,
                'reject_reason' => $request->message_rejectAbstract,
                'user_id'       => $user_id,
                'priority_id'   => $priority_role->priority_id,
            ]);



            $user = DB::table('users')->where('id', $user_id)->first();

            $vendor     = DB::table('purchase_vendors')->where('id', $bill->purchase_vendor_id)->first();
            $vendorUser = DB::table('users')->where('id', $vendor->user_id)->first();

            $templateData = DB::table('email_templates')->where('template_key', 'abstract_reject_mail')->first();

            if (! $templateData) {
                throw new \Exception('Email template not found.');
            }

            $dynamicData = [
                'employee_name' => $user->name,
                'bill_no'       => 'Bill#' . str_pad($bill->id, 3, '0', STR_PAD_LEFT),
                'bill_date'     => $bill->bill_date,
                'vendor_name'   => $vendorUser->name ?? '',
                'reject_reason' => $request->message_rejectAbstract,
            ];

            $subject  = $templateData->subject;
            $template = $templateData->template;
            foreach ($dynamicData as $key => $value) {
                $subject  = str_replace("[$key]", $value, $subject);
                $template = str_replace("[$key]", $value, $template);
            }

            $emailData = [
                'subject' => $subject,
                'message' => $template,
            ];

            if (! empty($vendorUser->email)) {
                Mail::to($vendorUser->email)->send(new DynamicEmail($emailData));
                
                 $approver   = auth()->user();
                $notifyData = [
                    'bill_id'     => $bill->id,
                    'bill_no'     => $bill->purchase_bill_number,
                    'total'       => $bill->total,
                    'approved_by' => $approver->name,
                    'title'       => 'Abstract Rejected',
                    'message'     => 'Bill #' . $bill->purchase_bill_number . ' Rejected by ' . $approver->name,
                ];

                $creator = User::find($vendorUser->id);
                if ($creator) {
                    $creator->notify(new BillApproveByFinalAuthority($notifyData));
                }
            }

            return response()->json(['success' => true, 'message' => 'Bill rejected and email sent to vendor successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Bill not found.']);
        }
    }


// public function getAbstractRejectReason($bill_id)
// {
//     $reason = DB::table('abstract_reject_history as a')
//         ->join('users as b', 'a.user_id', '=', 'b.id')
//         ->where('a.bill_id', $bill_id)
//         ->orderBy('a.created_at', 'desc')
//         ->select('a.*', 'b.name as user_name')
//         ->first();

//     if ($reason) {
//         // Add formatted date
//         $reason->formatted_date = Carbon::parse($reason->created_at)->format('M d, Y h:i A');
//         // Example: "Aug 05, 2025 10:30 AM"

//         return response()->json(['status' => true, 'data' => $reason]);
//     } else {
//         return response()->json(['status' => false, 'message' => 'No reject reason found.']);
//     }
// }

public function getAbstractRejectReason($bill_id)
{
    $reasons = DB::table('abstract_reject_history as a')
        ->join('users as b', 'a.user_id', '=', 'b.id')
        ->where('a.bill_id', $bill_id)
        ->orderBy('a.created_at', 'desc')
        ->select('a.*', 'b.name as user_name')
        ->get();

    if ($reasons->isNotEmpty()) {
        // Format the date for each reason
        $reasons->transform(function ($item) {
            $item->formatted_date = Carbon::parse($item->created_at)->format('M d, Y h:i A');
            return $item;
        });

        return response()->json(['status' => true, 'data' => $reasons]);
    } else {
        return response()->json(['status' => false, 'message' => 'No reject reasons found.']);
    }
}

}
