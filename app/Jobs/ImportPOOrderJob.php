<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Project;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\UniversalSearchTrait;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\ProjectActivity;
use App\Traits\ExcelImportable;

use App\Models\BudgetHead;
use App\Models\BudgetHeadDetail;
use App\Models\BudgetHeadName;
use App\Models\Product;
use App\Models\UnitType;
use App\Models\Tax;

use Modules\Purchase\Entities\PurchaseItem;
use Modules\Purchase\Entities\PurchaseItemTax;
use Modules\Purchase\Entities\PurchaseOrder; 
use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Str;
  
class ImportPOOrderJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;
    private $orderId;
    public function __construct($row, $columns, $company = null, $orderId = null)
        {
            $this->row = $row;
            $this->columns = $columns;
            $this->company = $company;
            $this->orderId = $orderId;
        }


    /**
     * Execute the job.
     *
     * @return void
     */ 
    public function handle()
{
     

    if ($this->isColumnExists('wbs') && $this->isColumnExists('item_name')) {
        $client = null;

        if (!empty($this->isColumnExists('client_email'))) {
            // user that has client role
            $client = User::where('email', $this->getColumnValue('client_email'))->whereHas('roles', function ($q) {
                $q->where('name', 'client');
            })->first();
        }

        DB::beginTransaction();
        try {
            $unitTypeName = $this->getColumnValue('unit_id');
            $companyId = $this->company?->id;
            $wbs = trim($this->getColumnValue('wbs'));
            $quantity = (float) preg_replace('/[^0-9.]/', '', $this->getColumnValue('quantity'));
            $unit_price = (float) preg_replace('/[^0-9.]/', '', $this->getColumnValue('unit_price'));
            // $gst = (float) preg_replace('/[^0-9.]/', '', $this->getColumnValue('gst'));
            // $retention_amount = (float) preg_replace('/[^0-9.]/', '', $this->getColumnValue('retention_amount'));

            $rawGst = $this->getColumnValue('gst');
            $gst = $rawGst === null || trim($rawGst) === '' ? 18 : (float) preg_replace('/[^0-9.]/', '', $rawGst);
            
            $rawRetention = $this->getColumnValue('retention_amount');
            $retention_amount = $rawRetention === null || trim($rawRetention) === '' ? 5 : (float) preg_replace('/[^0-9.]/', '', $rawRetention);
            
            // $gst = !empty($gst) ? $gst : 18;
            $gst = isset($gst) ? $gst : 18;
            $unit_price = !empty($unit_price) ? $unit_price : 0;
            $quantity = !empty($quantity) ? $quantity : 0;
            $amount = $unit_price * $quantity;
            $wbs = !empty($wbs) ? $wbs : '-';
            $retention_amount = isset($retention_amount) ? $retention_amount : 5;
            $unitTypeName = !empty($unitTypeName) ? $unitTypeName : '-';
            $gstid = Tax::firstOrCreate(
                ['rate_percent' => $gst]
            );

            $gstAmount = ($amount * $gstid->rate_percent) / 100;
            
            $retention = ($amount * $retention_amount)/100;

            // Check if the budget head exists, otherwise create a new one
            $budgetHead = BudgetHeadName::firstOrCreate(
                ['wbs' => $wbs]
            );
             
            $query = PurchaseOrder::where('id', $this->orderId);  
            $poSub = $query->first();
            
            if (!$poSub) {
                throw new \Exception("PurchaseOrder not found for ID: " . $this->orderId);
            }
            
            $projectId = $poSub->project_id;
            $subProjectId = $poSub->subproject_id;
                $query = BudgetHead::where('project_id', $projectId)
                   ->where('sub_project_id', $subProjectId)
                   ->select('id');

                // Log the SQL query with bindings
                $sql = str_replace_array('?', $query->getBindings(), $query->toSql());
                 
                $bhid = $query->value('id');

            // Check if unit type exists, otherwise create a new one
            $unitType = UnitType::firstOrCreate(
                ['unit_type' => $unitTypeName, 'company_id' => $companyId],
                ['default' => 0]
            );

            // Insert Product
            $Product = new Product();
            $Product->company_id = $companyId;
            $Product->department_id = 1;
            $Product->name = $this->getColumnValue('item_name');
            $Product->price = $unit_price;
            $Product->purchase_price = $unit_price;
            $Product->purchase_information = 1;
            $Product->taxes = implode(',', ["$gstid->id"]);
            $Product->category_id = 1;
            $Product->type = 'goods';
            $Product->status = 'active';
            $Product->unit_id = $unitType->id;
            $Product->save();

            // Insert PurchaseItem
            $PurchaseItem = new PurchaseItem();
            $PurchaseItem->company_id = $companyId;
            $PurchaseItem->department_id = 1;
            $PurchaseItem->budget_head_id = $bhid;
            $PurchaseItem->purchase_order_id = $this->orderId;
            $PurchaseItem->unit_id = $unitType->id;
            $PurchaseItem->product_id = $Product->id;
            $PurchaseItem->wbs = $wbs;
            $PurchaseItem->budget_head = $budgetHead->id;
            $PurchaseItem->item_name = $this->getColumnValue('item_name');
            $PurchaseItem->type = 'item';
            $PurchaseItem->retention_amount = $retention_amount;
            $PurchaseItem->retention = $retention;
            $PurchaseItem->gst_amount = $gstAmount;
            $PurchaseItem->quantity = $quantity;
            $PurchaseItem->unit_price = $unit_price;
            $PurchaseItem->tax_ids = $gstid->id;
            $PurchaseItem->taxes_percent = $gstid->rate_percent;
            $PurchaseItem->amount = $amount;
            $PurchaseItem->save();

            // Insert PurchaseItemTax
            $PurchaseItemTax = new PurchaseItemTax();
            $PurchaseItemTax->purchase_order_id = $this->orderId;
            $PurchaseItemTax->purchase_item_id = $PurchaseItem->id;
            $PurchaseItemTax->tax_id = $gstid->id;
            $PurchaseItemTax->save();

            // Update Purchase Order Subtotal and Total
            
            DB::table('purchase_items')
                    ->where('purchase_order_id', $this->orderId)
                    ->where('status', 2)
                    ->delete();


            $subTotal = PurchaseItem::where('purchase_order_id', $this->orderId)->sum('amount');
            $taxAmount = PurchaseItem::where('purchase_order_id', $this->orderId)->sum('gst_amount');

            $total = $subTotal + $taxAmount;

            PurchaseOrder::where('id', $this->orderId)->update([
                'sub_total' => $subTotal,
                'total' => $total,
                'taxAmount' => $taxAmount, 
                'status' =>'true',
            ]);

            DB::commit();
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
           \Log::error("ImportPOOrderJob failed: " . $e->getMessage(), [
            'userId' => $this->userId ?? null,
            'trace' => $e->getTraceAsString()
        ]);
        } catch (\Exception $e) {
          \Log::error("ImportPOOrderJob failed: " . $e->getMessage(), [
            'userId' => $this->userId ?? null,
            'trace' => $e->getTraceAsString()
        ]);
        }
    } else {
       \Log::error("ImportPOOrderJob failed: " . $e->getMessage(), [
            'userId' => $this->userId ?? null,
            'trace' => $e->getTraceAsString()
        ]);
    }
}


    public function logProjectActivity($projectId, $text)
    {
        $activity = new ProjectActivity();
        $activity->project_id = $projectId;
        $activity->activity = $text;
        $activity->save();
    }

}
