<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDelayedBills extends Command
{
    protected $signature   = 'bills:check-delayed';
    protected $description = 'Check and store delayed bills for users/vendors';

    public function handle()
    {
        $today = Carbon::today()->toDateString();

        // Step 1: Get all pending bills with their approval records
        $bills = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->join('approval_bills_by as ab', 'ab.bill_id', '=', 'pb.id')
            ->select('pb.id as bill_id', 'po.project_id', 'ab.approval_status', 'ab.created_at', 'ab.updated_at')
            ->where('pb.status', 'pending')
            ->get();

        foreach ($bills as $bill) {
            $billId    = $bill->bill_id;
            $projectId = $bill->project_id;
            $approvalStatus = $bill->approval_status;

            // Step 2: Get all priority_role users for this bill & calculate delay
            $priorityUsers = DB::table('priority_role as pr')
                    ->join('approval_bills_by as ab', function($join) use ($approvalStatus, $billId) {
                        $join->on('ab.approval_status', '=', 'pr.priority_id')
                             ->where('ab.bill_id', $billId);
                    })
                    ->select(
                        'pr.user_id',
                        'ab.approval_status',
                        'pr.priority_id',
                        'pr.project_id',
                        'pr.approval_time',
                        'pr.approval_unit',
                        'ab.updated_at',
                        DB::raw("GREATEST(
                            0,
                            DATEDIFF(
                                NOW(),
                                CASE
                                    WHEN pr.approval_unit = 'hours' THEN DATE_ADD(ab.updated_at, INTERVAL pr.approval_time HOUR)
                                    ELSE DATE_ADD(ab.updated_at, INTERVAL pr.approval_time DAY)
                                END
                            )
                        ) AS delay_days"),
                        // APPROVE DATE = updated_at + approval_time
                        DB::raw("CASE
                                    WHEN pr.approval_unit = 'hours' THEN DATE_ADD(ab.updated_at, INTERVAL pr.approval_time HOUR)
                                    ELSE DATE_ADD(ab.updated_at, INTERVAL pr.approval_time DAY)
                                 END AS approve_date")
                    )
                    ->where('pr.project_id', $projectId)
                    ->where('pr.priority_id', $approvalStatus)
                    ->groupBy('pr.priority_id', 'pr.user_id', 'pr.project_id', 'pr.approval_time', 'pr.approval_unit', 'ab.updated_at', 'ab.approval_status')
                    ->get();


            foreach ($priorityUsers as $pu) {
                // Step 3: Check if record exists
                $existing = DB::table('purchase_bills_delay')
                    ->where('bill_id', $billId)
                    ->where('user_id', $pu->user_id)
                    ->first();

                $billDueDate = Carbon::parse($bill->updated_at)->format('Y-m-d'); // Using updated_at as reference

                if ($existing) {
                    // Update only delay info
                    DB::table('purchase_bills_delay')
                        ->where('id', $existing->id)
                        ->update([
                            'delay_date' => $today,
                            'delay_days' => $pu->delay_days,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Insert new record
                    DB::table('purchase_bills_delay')->insert([
                        'bill_id'    => $billId,
                        'user_id'    => $pu->user_id,
                        'bill_date'  => $billDueDate,
                        'delay_date' => $today,
                        'delay_days' => $pu->delay_days,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->info("Delayed bills check completed for all bills and users.");
        return 0;
    }
}
