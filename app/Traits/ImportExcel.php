<?php

namespace App\Traits;

use App\Helper\Reply;
use App\Helper\Files;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use App\Models\BudgetHeadName;
use ReflectionClass;
use Modules\Purchase\Entities\PurchaseOrder; 
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Request;



trait ImportExcel
{ 
    
    public function importFileProcess($request, $importClass)
    {
        // get class name from $importClass
        $this->importClassName = (new ReflectionClass($importClass))->getShortName();

        $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);
        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0];
        $this->hasHeading = $request->has('heading');
        $this->heading = array();
        $this->fileHeading = array();

        $this->columns = $importClass::fields();
        $this->importMatchedColumns = array();
        $this->matchedColumns = array();

        if ($this->hasHeading) {
            $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];

            // Excel Format None for get Heading Row Without Format and after change back to config
            HeadingRowFormatter::default('none');
            $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
            HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));

            array_shift($excelData);
            $this->matchedColumns = collect($this->columns)->whereIn('id', $this->heading)->pluck('id');
            $importMatchedColumns = array();

            foreach ($this->matchedColumns as $matchedColumn) {
                $importMatchedColumns[$matchedColumn] = 1;
            }

            $this->importMatchedColumns = $importMatchedColumns;
        }

        $this->importSample = array_slice($excelData, 0, 5);
    }

    public function importJobProcess($request, $importClass, $importJobClass)
    {
        // get class name from $importClass
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');
        // Get index of an array not null value with key
        $columns = array_filter($request->columns, function ($value) {
            return $value !== null;
        });

        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file))[0];

        if ($request->has_heading) {
            array_shift($excelData);
        }

        $jobs = [];

        foreach ($excelData as $row) {

            $jobs[] = (new $importJobClass($row, $columns, company()));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }





    public function importFileProcessBudget($request, $importClass,$budgetHeadId = null)
        {
            //  
              $this->budgetHeadId = $budgetHeadId; 
            $this->importClassName = (new ReflectionClass($importClass))->getShortName();
    
            $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);
            $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0];
            $this->hasHeading = $request->has('heading');
            $this->heading = array();
            $this->fileHeading = array();
    
            $this->columns = $importClass::fields();
            $this->importMatchedColumns = array();
            $this->matchedColumns = array();
    
            if ($this->hasHeading) {
                $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
    
                // Excel Format None for get Heading Row Without Format and after change back to config
                HeadingRowFormatter::default('none');
                $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
                HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));
    
                array_shift($excelData);
                $this->matchedColumns = collect($this->columns)->whereIn('id', $this->heading)->pluck('id');
                $importMatchedColumns = array();
    
                foreach ($this->matchedColumns as $matchedColumn) {
                    $importMatchedColumns[$matchedColumn] = 1;
                }
    
                $this->importMatchedColumns = $importMatchedColumns;
            }
    
            $this->importSample = array_slice($excelData, 0, 5);
        }
    
    public function importJobProcessBudget($request, $importClass, $importJobClass, $budgetHeadId = null)
    {
        $importClassName = (new ReflectionClass($importClass))->getShortName();
    
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');
    
        $columns = array_filter($request->columns, fn($value) => $value !== null);
    
        $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file);
        $excelData = Excel::toArray(new $importClass, $filePath)[0];
    
        if (empty($excelData)) {
            return response()->json(['status' => 'error', 'message' => 'No data found in the file.']);
        }
    
        // Check if there is at least two rows to extract the second row
        if (count($excelData) >= 1) {
             
            // Remove the first two rows
            $excelData = array_slice($excelData, 1);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Insufficient data.']);
        }
    
        $jobs = [];
        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company(), $budgetHeadId));
        }
    
        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();
    
        Files::deleteFile($request->file, Files::IMPORT_FOLDER);
    
        return $batch;
    }
    
    
    
    public function importFileProcessPo($request, $importClass,$orderId = null)
    {
        //
          $this->orderId = $orderId;
          
        // get class name from $importClass
        $this->importClassName = (new ReflectionClass($importClass))->getShortName();

        $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);
        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0];
        $this->hasHeading = $request->has('heading');
        $this->heading = array();
        $this->fileHeading = array();

        $this->columns = $importClass::fields();
        $this->importMatchedColumns = array();
        $this->matchedColumns = array();

        if ($this->hasHeading) {
            $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];

            // Excel Format None for get Heading Row Without Format and after change back to config
            HeadingRowFormatter::default('none');
            $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
            HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));

            array_shift($excelData);
            $this->matchedColumns = collect($this->columns)->whereIn('id', $this->heading)->pluck('id');
            $importMatchedColumns = array();

            foreach ($this->matchedColumns as $matchedColumn) {
                $importMatchedColumns[$matchedColumn] = 1;
            }

            $this->importMatchedColumns = $importMatchedColumns;
        }

        $this->importSample = array_slice($excelData, 0, 5);
    }

     public function importJobProcessPo($request, $importClass, $importJobClass, $orderId = null, $budgetHeadId = null){
    $importClassName = (new \ReflectionClass($importClass))->getShortName();

    // Clear and flush queue
    Artisan::call('queue:clear database --queue=' . $importClassName);
    Artisan::call('queue:flush');

    // Prepare columns
    $columns = array_filter($request->columns, fn($value) => $value !== null);

    // Get Excel data
    $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file);
    $excelData = Excel::toArray(new $importClass, $filePath)[0];

    if (empty($excelData)) {
        return response()->json(['status' => 'error', 'message' => 'No data found in the file.']);
    }

    // Remove header row
    if (count($excelData) >= 1) {
        $excelData = array_slice($excelData, 1); // remove first row
    } else {
        return response()->json(['status' => 'error', 'message' => 'Insufficient data.']);
    }

    $query = PurchaseOrder::where('id', $orderId);
    $poSub = $query->first();

    if (!$poSub) {
        throw new \Exception("PurchaseOrder not found for ID: " . $orderId);
    }

    $projectId = $poSub->project_id;
    $subProjectId = $poSub->subproject_id;
    $action = $poSub->action;

    $project = Project::find($projectId);
    $projectName = $project ? $project->project_name : null;

    $subProject = Task::find($subProjectId);
    $subProjectName = $subProject ? $subProject->heading : null;

    // Store unmatched WBS for error reporting
    $unmatchedWbs = [];
    
    foreach ($excelData as $index => $row) {
        $wbs = trim($row[0] ?? '');
        $shouldLookup = ($wbs !== '' && $wbs !== '-');

        if ($shouldLookup) {
            $originalWbs = $wbs; // Store original WBS for error message
            
            if (preg_match('/^(.*-)(\d+)(\.\d+.*)?$/', $wbs, $matches)) {
                $baseWbs = trim($matches[1] . $matches[2]);
            } else {
                $baseWbs = $wbs;
            }

            $query = BudgetHeadName::whereRaw('TRIM(wbs) = ?', [$baseWbs])
                ->where('budget_head_id', $budgetHeadId);
            $budgetHead = $query->first();

            if (!$budgetHead) {
                if($action ==='new'){
                    PurchaseOrder::where('id', $orderId)->delete();
                }
                
                // Add to unmatched WBS array
                $unmatchedWbs[] = $originalWbs;
            }
        }
    }

    // If there are unmatched WBS entries, return error
    if (!empty($unmatchedWbs)) {
        $unmatchedWbsList = implode(', ', $unmatchedWbs);
        $errorMessage = "Could not match Budget Head(s) for WBS: {$unmatchedWbsList} on Project: {$projectName} under Subproject: {$subProjectName}.";
        return response()->json(['status' => 'error', 'message' => $errorMessage]);
    }

    // All rows are valid, proceed to dispatch jobs
    $jobs = [];
    foreach ($excelData as $row) {
        $jobs[] = new $importJobClass($row, $columns, company(), $orderId);
    }

    $batch = Bus::batch($jobs)
        ->onConnection('database')
        ->onQueue($importClassName)
        ->name($importClassName)
        ->dispatch();

    Files::deleteFile($request->file, Files::IMPORT_FOLDER);

    return $batch;
} 


    
     public function importFileProcessPoStages($request, $importClass,$orderId = null,$stages=null)
    {
        //
          $this->orderId = $orderId;
          $this->stages = $stages;
          
          

        // get class name from $importClass
        $this->importClassName = (new ReflectionClass($importClass))->getShortName();

        $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);
        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0];
        $this->hasHeading = $request->has('heading');
        $this->heading = array();
        $this->fileHeading = array();

        $this->columns = $importClass::fields();
        $this->importMatchedColumns = array();
        $this->matchedColumns = array();

        if ($this->hasHeading) {
            $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];

            // Excel Format None for get Heading Row Without Format and after change back to config
            HeadingRowFormatter::default('none');
            $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
            HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));

            array_shift($excelData);
            $this->matchedColumns = collect($this->columns)->whereIn('id', $this->heading)->pluck('id');
            $importMatchedColumns = array();

            foreach ($this->matchedColumns as $matchedColumn) {
                $importMatchedColumns[$matchedColumn] = 1;
            }

            $this->importMatchedColumns = $importMatchedColumns;
        }

        $this->importSample = array_slice($excelData, 0, 5);
    }

    public function importJobProcessPoStages($request, $importClass, $importJobClass, $orderId = null,$stages=null)
    {
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        $columns = array_filter($request->columns, fn($value) => $value !== null);

        $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file);
        $excelData = Excel::toArray(new $importClass, $filePath)[0];

        if (empty($excelData)) {
            return response()->json(['status' => 'error', 'message' => 'No data found in the file.']);
        }

        if (count($excelData) >= 2) {
            $secondRow = $excelData[1]; 
            // Remove the first two rows
            $excelData = array_slice($excelData, 2);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Insufficient data.']);
        }

        $jobs = [];
        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company(), $orderId,$stages));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }


}
