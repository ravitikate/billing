<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class StagesImport implements ToArray
{
    public static function fields(): array
    {
        return [
            ['id' => 'wbs', 'name' => __('modules.projects.WBS'), 'required' => 'Yes'],
            ['id' => 'budgetHead', 'name' => __('modules.projects.budgetHead'), 'required' => 'Yes'],
            ['id' => 'item_name', 'name' => __('modules.projects.item_name'), 'required' => 'Yes'],
            ['id' => 'completion_as_per_actual', 'name' => __('modules.projects.completion_as_per_actual'), 'required' => 'Yes'], 
            ['id' => 'retention_amount', 'name' => __('modules.projects.retention_amount'), 'required' => 'Yes'],
            ['id' => 'amount', 'name' => __('modules.projects.amount'), 'required' => 'Yes'],
        ];
    }

    public function array(array $array): array
    {
        return $array;
    }
}
