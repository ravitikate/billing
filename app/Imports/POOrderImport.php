<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class POOrderImport implements ToArray
{
    
    public static function fields(): array
    { 
        return [
            ['id' => 'wbs', 'name' => __('modules.projects.WBS'), 'required' => 'Yes'], 
            ['id' => 'item_name', 'name' => __('modules.projects.item_name'), 'required' => 'Yes'],
            ['id' => 'unit_id', 'name' => __('modules.projects.unit_id'), 'required' => 'Yes'],
            ['id' => 'quantity', 'name' => __('modules.projects.quantity'), 'required' => 'Yes'],
            ['id' => 'unit_price', 'name' => __('modules.projects.unit_price'), 'required' => 'Yes'],
            ['id' => 'retention_amount', 'name' => __('app.retention_amount'), 'required' => 'Yes'],
            ['id' => 'gst', 'name' => __('modules.projects.gst'), 'required' => 'Yes'],
        ];
    }

    public function array(array $array): array
    {
        return $array;
    }
}
