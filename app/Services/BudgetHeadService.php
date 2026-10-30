<?php 


namespace App\Services;

class BudgetHeadService
{
    private static $budgetHeadId;

    public static function setBudgetHeadId($id)
    {
        self::$budgetHeadId = $id;
    }

    public static function getBudgetHeadId()
    {
        if (!self::$budgetHeadId) {
            throw new \Exception('Budget Head ID is not set.');
        }
        return self::$budgetHeadId;
    }
}


?>