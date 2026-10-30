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
use App\Models\BudgetHeadName;
use App\Models\BudgetHeadDetail;

class ImportBudgetHeadJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company; 
    private $budgetHeadId;
    public function __construct($row, $columns, $company = null, $budgetHeadId = null)
        {
            $this->row = $row;
            $this->columns = $columns;
            $this->company = $company;
            $this->budgetHeadId = $budgetHeadId;
        }


    /**
     * Execute the job.
     *
     * @return void
     */
   public function handle()
{
    // Check if required columns exist
    if (!$this->isColumnExists('budget_head') || !$this->isColumnExists('budget_cost')) {
        return $this->failJob(__('messages.invalidData'));
    }

    $budgetHeadName = $this->getColumnValue('budget_head');
    $budgetCost = $this->getColumnValue('budget_cost');
    $wbs = trim($this->getColumnValue('wbs'));
    $type = strtolower($this->getColumnValue('type'));

    if (empty($budgetHeadName) || empty($budgetCost) || empty($wbs) || empty($type)) {
        return $this->failJob(__('messages.missingRequiredData'));
    }

    // Determine type mapping
    if (str_starts_with($type, 'd')) {
        $type = 'dev';
    } elseif (str_starts_with($type, 'b')) {
        $type = 'build';
    } elseif (str_starts_with($type, 't')) {
        $type = 'tech';
    }

    try {
        DB::beginTransaction();

        // Optional: Fetch client user (not used in logic currently)
        $client = null;
        if ($this->isColumnExists('client_email')) {
            $clientEmail = $this->getColumnValue('client_email');
            if (!empty($clientEmail)) {
                $client = User::where('email', $clientEmail)
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'client');
                    })->first();
            }
        }

        // Validate budgetHeadId before use
        if (empty($this->budgetHeadId)) {
            throw new Exception('Missing budgetHeadId');
        }

        // Create BudgetHeadName
        $budgetHead = new BudgetHeadName();
        $budgetHead->name = $budgetHeadName;
        $budgetHead->budget_head_id = $this->budgetHeadId;
        $budgetHead->type = $type;
        $budgetHead->wbs = $wbs;
        $budgetHead->save();

        // Create BudgetHeadDetail
        $budgetHeadDetail = new BudgetHeadDetail();
        $budgetHeadDetail->budget_head_id = $this->budgetHeadId;
        $budgetHeadDetail->wbs = $wbs;
        $budgetHeadDetail->budget_head = $budgetHead->id;
        $budgetHeadDetail->budget_cost = $budgetCost;
        $budgetHeadDetail->save();

        // Update BudgetHead with total_cost
        // $budgetHeadSum = BudgetHeadDetail::where('budget_head_id', $this->budgetHeadId)->sum('budget_cost');
        DB::table('budget_head')->where('id', $this->budgetHeadId)->update([ 
            'status' => 1,
            'validate' => 2,
            'updated_at' => Carbon::now(),
        ]);

        DB::commit();
    } catch (InvalidFormatException $e) {
        DB::rollBack();
        Log::error('Invalid date format in job', ['error' => $e->getMessage()]);
        return $this->failJob(__('messages.invalidDate'));
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Job failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return $this->failJobWithMessage($e->getMessage());
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
