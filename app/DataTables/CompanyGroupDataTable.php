<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;

class CompanyGroupDataTable extends DataTable
{
    public function query()
    {
        return DB::table('company_group')
            ->select('id', 'company_name', 'status')
            ->where('status', 1);
    }

    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return '<button
                type="button"
                class="btn btn-sm btn-primary editCompanyGroup"
                data-id="'.$row->id.'"
                data-name="'.e($row->company_name).'"
            >Edit</button>';
            });
    }

    public function html()
    {
        return $this->builder()
            ->columns([
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
                ['data' => 'company_name', 'name' => 'company_name', 'title' => 'Company Name'],
            ])
            ->minifiedAjax()
            ->addAction(['width' => '80px'])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }
}
