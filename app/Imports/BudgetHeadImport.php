<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class BudgetHeadImport implements ToArray
{
    public static function fields(): array
    {
        return [
            ['id' => 'budget_head', 'name' => __('modules.projects.budgetHead'), 'required' => 'Yes'],
            ['id' => 'wbs', 'name' => __('modules.projects.WBS'), 'required' => 'Yes'],
           
            ['id' => 'budget_cost', 'name' => __('modules.projects.budgetedCost'), 'required' => 'Yes'],
             ['id' => 'type', 'name' => __('modules.projects.type'), 'required' => 'Yes'],
        ];
    }

    public function array(array $array): array
    {
        return $array;
    }
}
