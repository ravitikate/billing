<?php
namespace App\Console\Commands;

use App\Notifications\SendInvoiceByContractor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

use App\Notifications\BillApproveByFinalAuthority;
use App\Mail\DynamicEmail;
use Illuminate\Support\Facades\Mail;

class DurationExpireEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:duration-expire-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if approval duration expired and take action';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = DB::table('approval_bills_by as abb')
            ->join('priority_role as pr', 'pr.priority_id', '=', 'abb.approval_status')
            ->where('abb.status', 1)
            ->where('pr.approval_time', '!=', '')
            ->select('abb.*', 'pr.approval_time', 'pr.approval_unit', 'pr.user_id', 'pr.project_id', 'pr.priority_id')
            ->get();

        foreach ($data as $row) {
            $updatedAt    = Carbon::parse($row->updated_at);
            $approvalTime = (int) $row->approval_time;
            $unit         = strtolower(trim($row->approval_unit)); // 'days' or 'hours'

            // Add duration to updated_at
            if ($unit === 'days') {
                $deadline = $updatedAt->copy()->addDays($approvalTime);
            } elseif ($unit === 'hours') {
                $deadline = $updatedAt->copy()->addHours($approvalTime);
            } else {
                Log::warning("Unknown approval unit for row ID {$row->id}: {$unit}");
                continue;
            }

            // Check expiration
            if (now()->greaterThan($deadline)) {

                $userID      = $row->user_id;
                $priority_id = $row->priority_id;

                $projectID    = $row->project_id;
                $billId       = $row->bill_id;
                $billDetails  = DB::table('purchase_bills')->where('id', $billId)->first();
                $poDetails    = DB::table('purchase_orders')->where('id', $billDetails->purchase_order_id)->first();
                $subprojectID = $poDetails->subproject_id;

                $priorityCount = DB::table('priority_role')->where('project_id', $projectID)->count();

                $current_employeeDetail = DB::table('users')->where('id', $userID)->first();

                if ($priorityCount > $priority_id) {
                    $priority_id1 = $priority_id + 1;
                } elseif ($priorityCount == $priority_id) {
                    $priority_id1 = $priority_id;
                }

                $p_role              = DB::table('priority_role')->where('project_id', $projectID)->where('priority_id', $priority_id1)->first();
                $next_userID         = $p_role->user_id;
                $naxt_employeeDetail = DB::table('users')->where('id', $next_userID)->first();

                $vDetail      = DB::table('purchase_vendors')->where('id', $billDetails->purchase_vendor_id)->first();
                $vendorDetail = DB::table('users')->where('id', $vDetail->user_id)->first();

                $project      = DB::table('projects')->where('id', $projectID)->first();
                $project_name = $project ? $project->project_name : null;

                $subProject      = DB::table('tasks')->where('id', $subprojectID)->first();
                $subProject_name = $subProject->heading . ' ' . $subProject->task_short_code;

                $email_templates = DB::table('email_templates')->where('template_key', 'the_duration_has_expired_cron_job')->first();

                if (! $email_templates) {
                    return response()->json(['success' => false, 'message' => 'Email template not found.']);
                }

                $dynamicData = [
                    'current_employee_name' => $current_employeeDetail->name,
                    'next_employee_name'    => $naxt_employeeDetail->name,
                    'bill_no'               => 'Bill#' . str_pad($billId, 3, '0', STR_PAD_LEFT),
                    'bill_date'             => $billDetails->bill_date,
                    'vendor_name'           => $vendorDetail->name,
                    'sub_total'             => $billDetails->sub_total,
                    'tax_total'             => $billDetails->tax_total,
                    'total'                 => $billDetails->total,
                ];

                try {
                    // Send email to Employee
                    if (! empty($naxt_employeeDetail->email)) {
                        $subject_emp  = $email_templates->subject;
                        $template_emp = $email_templates->template;

                        foreach ($dynamicData as $key => $value) {
                            $subject_emp  = str_replace("[$key]", $value, $subject_emp);
                            $template_emp = str_replace("[$key]", $value, $template_emp);
                        }

                        $emailDataEmp = [
                            'subject'    => $subject_emp,
                            'greeting'   => 'Hi there!',
                            'message'    => $template_emp,
                            'closing'    => 'We appreciate your support!',
                            'salutation' => 'Cheers, The Team',
                        ];

                        // Notification::route('mail', $naxt_employeeDetail->email)
                        //     ->notify(new SendInvoiceByContractor($emailDataEmp));
                        
                        Mail::to($naxt_employeeDetail->email)->send(new DynamicEmail($emailData));
                        
                         $notifyData = [
                    'bill_id'     => $billDetails->id,
                    'bill_no'     => $billDetails->purchase_bill_number,
                    'total'       => $billDetails->total,
                    'title'       => 'The duration has expired',
                    'message'     => $subject_emp,
                ];

                $creator = User::find($naxt_employeeDetail->id);
                if ($creator) {
                    $creator->notify(new BillApproveByFinalAuthority($notifyData));
                }


                        DB::table('approval_bills_by')
                            ->where('id', $row->id)
                            ->update([
                                'approval_status' => $priority_id1,
                                'updated_at'      => now(),
                            ]);
                        DB::table('approval_bill_details')->insert([
                            'user_id'         => $userID,
                            'bill_id'         => $billId,
                            'approval_status' => '',
                            'message'         => 'Approval Time Expire',
                            'created_at'      => now(),
                        ]);
                    }

                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
                }

                Log::info("Approval expired for ID {$row->id}. Deadline was: {$deadline} - {$naxt_employeeDetail->name} - {$naxt_employeeDetail->email}");

            }
        }
        
        $dataDue = DB::table('purchase_bills as pb')
            ->join('purchase_orders as po', 'po.id', '=', 'pb.purchase_order_id')
            ->where('pb.status', 'open')
            ->whereRaw("CURDATE() > DATE_ADD(pb.abstract_approve, INTERVAL po.billing_duration DAY)")
            ->select('pb.id') // only fetch id
            ->get();
        
        // Update each matching bill
        foreach ($dataDue as $bill) {
            DB::table('purchase_bills')
                ->where('id', $bill->id)
               ->update([
                    'status' => 'due',
                    'updated_at' => Carbon::now('Asia/Kolkata')  
                ]);
        }
        $this->info('Checked all approvals for expiration.');
    }
}
