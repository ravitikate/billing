<?php

namespace Modules\Purchase\Observers;

use Exception;
use App\Helper\Files;
use Modules\Purchase\Entities\PurchaseItem;
use Modules\Purchase\Entities\PurchaseOrder;
use Modules\Purchase\Entities\PurchaseItemTax;
use Modules\Purchase\Entities\PurchaseItemImage;
use Modules\Purchase\Events\NewPurchaseOrderEvent;
use Modules\Purchase\Entities\PurchaseOrderHistory;
use Modules\Purchase\Entities\PurchaseStockAdjustment;

class PurchaseOrderObserver
{
    public function saving(PurchaseOrder $order)
    {
        if (!isRunningInConsoleOrSeeding() && user()) {
            $order->last_updated_by = user()->id;
            $order->calculate_tax = request()->has('calculate_tax') ? request()->calculate_tax : $order->calculate_tax;
        }
    }

    public function creating(PurchaseOrder $order)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $order->send_status = (request()->type === 'send' || request()->type === 'mark_as_send') ? 1 : 0;
            if (request()->type === 'draft') {
                $order->purchase_status = 'draft';
            }
        }

        $order->company_id = company() ? company()->id : null;
        $order->added_by = user() ? user()->id : null;
    }

    public function created(PurchaseOrder $order)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($order->vendor && request()->type === 'send') {
                event(new NewPurchaseOrderEvent($order, $order->vendor));
            }

            $request = request();
            $items = $request->item_name ?? [];
            $itemsSummary = $request->item_summary ?? [];
            $cost_per_item = $request->cost_per_item ?? [];
            $hsn_sac_code = $request->wbc ?? [];
            $quantity = $request->quantity ?? [];
            $unitId = $request->unit_id ?? '1';
            $product = $request->product_id ?? [];
            $amount = $request->product_amount ?? [];
            $order_item_image = $request->order_item_image ?? [];
            $order_item_image_url = $request->order_item_image_url ?? [];
            $taxes = $request->taxes ?? [];
            $tax_id = $request->tax_no ?? [];
            $department_id = $request->department_id ?? [];

            if (is_array($items)) {
                foreach ($items as $key => $item) {
                    if (!is_null($item)) {
                        $orderItem = PurchaseItem::create([
                            'purchase_order_id' => $order->id,
                            'item_name' => $item,
                            'item_summary' => $itemsSummary[$key] ?? '',
                            'type' => 'item',
                            'unit_id' => 1,
                            'product_id' => $product[$key] ?? null,
                            'hsn_sac_code' => $hsn_sac_code[$key] ?? null,
                            'quantity' => $quantity[$key] ?? 0,
                            'unit_price' => round($cost_per_item[$key] ?? 0, 2),
                            'amount' => round($amount[$key] ?? 0, 2),
                            'taxes_percent' => $taxes[$key] ?? [],
                            'tax_ids' => $tax_id[$key] ?? null,
                            'department_id' => $department_id[$key] ?? null,
                        ]);

                        if ($order->delivery_status === 'delivered') {
                            $inventory = PurchaseStockAdjustment::firstOrNew(['product_id' => $product[$key] ?? null]);
                            $inventory->net_quantity = ($inventory->net_quantity ?? 0) + ($quantity[$key] ?? 0);
                            $inventory->save();
                        }

                        if (isset($tax_id[$key])) {
                            PurchaseItemTax::create([
                                'purchase_order_id' => $order->id,
                                'purchase_item_id' => $orderItem->id,
                                'tax_id' => $tax_id[$key] ?? null,
                            ]);
                        }

                        if (isset($order_item_image[$key]) || isset($order_item_image_url[$key])) {
                            $filename = isset($order_item_image[$key]) ? Files::uploadLocalOrS3($order_item_image[$key], PurchaseItemImage::FILE_PATH . '/' . $orderItem->id . '/') : '';

                            PurchaseItemImage::create([
                                'purchase_item_id' => $orderItem->id,
                                'filename' => isset($order_item_image[$key]) ? $order_item_image[$key]->getClientOriginalName() : '',
                                'hashname' => $filename,
                                'size' => isset($order_item_image[$key]) ? $order_item_image[$key]->getSize() : '',
                                'external_link' => $order_item_image_url[$key] ?? '',
                            ]);
                        }
                    }
                }
            }

            $this->logOrderActivity(company()->id, $order->id, $order->vendor_id, user()->id, 'purchaseOrderCreated', 'Created');
        }
    }

    public function updating(PurchaseOrder $order)
    {
        if (!isRunningInConsoleOrSeeding() && (request()->type === 'send' || request()->type === 'mark_as_send')) {
            $order->send_status = 1;
        }
    }

    public function updated(PurchaseOrder $order)
    {
        foreach ($order->items as $item) {
            if ($order->delivery_status === 'delivered') {
                $inventory = PurchaseStockAdjustment::firstOrNew(['product_id' => $item->product_id]);
                $inventory->net_quantity = ($inventory->net_quantity ?? 0) + $item->quantity;
                $inventory->save();
            }
        }

        $this->logOrderActivity(company()->id, $order->id, $order->vendor_id, user()->id, 'purchaseOrderUpdated', 'Updated');

        $order->saveQuietly();
    }

    public function deleting(PurchaseOrder $order)
    {
        $orderItems = PurchaseItem::where('purchase_order_id', $order->id)->get();
        foreach ($orderItems as $orderItem) {
            Files::deleteDirectory(PurchaseItemImage::FILE_PATH . '/' . $orderItem->id);
        }
    }

    private function logOrderActivity($companyID, $orderID, $vendorID, $userID, $text, $label)
    {
        PurchaseOrderHistory::create([
            'company_id' => $companyID,
            'purchase_order_id' => $orderID,
            'purchase_vendor_id' => $vendorID,
            'user_id' => $userID,
            'details' => $text,
            'label' => $label,
        ]);
    }
}
