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

use App\Models\Product;
use App\Models\UnitType;
use App\Models\BudgetHeadName;
use Modules\Purchase\Entities\PurchaseItem;
use Modules\Purchase\Entities\PurchaseItemTax;
use Modules\Purchase\Entities\PurchaseOrder;

class ImportStagesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;
    private $orderId;
     private $stages;
    public function __construct($row, $columns, $company = null, $orderId = null,$stages = null)
        {
            $this->row = $row;
            $this->columns = $columns;
            $this->company = $company;
            $this->orderId = $orderId;
            $this->stages = $stages;
        }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // print_r($this->getColumnValue('budget_head')); exit;
        if ($this->isColumnExists('wbs') && $this->isColumnExists('item_name')) {
            $client = null;

            if (!empty($this->isColumnExists('client_email'))) {
                // user that have client role
                $client = User::where('email', $this->getColumnValue('client_email'))->whereHas('roles', function ($q) {
                    $q->where('name', 'client');
                })->first();
            }

            DB::beginTransaction();
            try {
                    // Get the unit type value
                    $unitTypeName = $this->getColumnValue('unit_id');
                    $companyId = $this->company?->id;
                $amount = (float) $this->getColumnValue('amount');
                $gstAmount = ($amount * 18) / 100;
                     $budgetHeadName = $this->getColumnValue('budgetHead');
            
                // Check if the budget head exists, otherwise create a new one
                        $budgetHead = BudgetHeadName::firstOrCreate(
                            ['name' => $budgetHeadName] 
                        );

                    // Insert Product
                    $Product = new Product();
                    $Product->company_id = $companyId;
                    $Product->department_id = 1;
                    $Product->name = $this->getColumnValue('item_name');
                    $Product->price = $this->getColumnValue('amount');
                    $Product->purchase_price = $this->getColumnValue('amount');
                    $Product->purchase_information = 1;
                    $Product->taxes = implode(',', ["1"]);
                    $Product->category_id = 1;
                    $Product->type = 'goods';
                    $Product->status = 'active';
                    $Product->unit_id = 1;
                    $Product->save();

                    // Insert PurchaseItem
                    $PurchaseItem = new PurchaseItem();
                    $PurchaseItem->company_id = $companyId;
                    $PurchaseItem->department_id = 1;
                    $PurchaseItem->purchase_order_id = $this->orderId;
                    $PurchaseItem->unit_id = 1;
                    $PurchaseItem->product_id = $Product->id;
                    $PurchaseItem->budget_head = $budgetHead->id;
                    $PurchaseItem->wbs = $this->getColumnValue('wbs');
                    $PurchaseItem->item_name = $this->getColumnValue('item_name');
                    $PurchaseItem->item_summary = $this->getColumnValue('completion_as_per_actual');
                    $PurchaseItem->type = 'item'; 
                    $PurchaseItem->quantity = 1;
                    $PurchaseItem->retention_amount = $this->getColumnValue('retention_amount');
                    $PurchaseItem->gst_amount = $gstAmount;
                    $PurchaseItem->unit_price = $this->getColumnValue('amount');
                    $PurchaseItem->tax_ids = 1;
                    $PurchaseItem->taxes_percent = 18;
                    $PurchaseItem->po_type = $this->stages;
                    $PurchaseItem->amount = $this->getColumnValue('amount');
                    $PurchaseItem->save();

                    $PurchaseItemTax = new PurchaseItemTax();
                    $PurchaseItemTax->purchase_order_id = $this->orderId;
                    $PurchaseItemTax->purchase_item_id = $PurchaseItem->id;
                    $PurchaseItemTax->tax_id = 1;
                    $PurchaseItemTax->save();

                    // Update Purchase Order Subtotal and Total
                    $subTotal = PurchaseItem::where('purchase_order_id', $this->orderId)->sum('amount');
                    $total = $subTotal; // You can apply additional taxes or discounts if required

                    PurchaseOrder::where('id', $this->orderId)->update([
                        // 'sub_total' => $subTotal,
                        'type' => $this->stages,
                    ]);



                // $this->logSearchEntry($Product->id, $Product->budget_head, 'projects.show', 'project', $Product->company_id);
                // $this->logProjectActivity($BudgetHeadDetail->id, 'messages.updateSuccess');

                DB::commit();
            } catch (InvalidFormatException $e) {
                DB::rollBack();
                $this->failJob(__('messages.invalidDate'));
            } catch (Exception $e) {
                DB::rollBack();
                $this->failJobWithMessage($e->getMessage());
            }

        }
        else {
            $this->failJob(__('messages.invalidData'));
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
