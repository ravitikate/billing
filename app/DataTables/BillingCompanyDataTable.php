<?php

namespace App\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;

class BillingCompanyDataTable extends DataTable
{
    public function query()
    {
        return  DB::table('billing_company as b')
        ->join('client_details as c', 'c.id', '=', 'b.company_group_id')
        ->select('b.*', 'c.company_name as company_group_name');
    }

    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return '<button
                type="button"
                class="btn btn-sm btn-primary editCompanyGroup btnAdd"
                data-id="'.$row->id.'"
                data-name="'.e($row->company_group_id).'"
                data-billname="'.e($row->billing_company_name).'"
            >Edit</button>';
            });
    }

    public function html()
    {
        return $this->builder()
            ->columns([
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
                ['data' => 'company_group_name', 'name' => 'company_group_name', 'title' => 'Company Group'],
                ['data' => 'billing_company_name', 'name' => 'billing_company_name', 'title' => 'Billing Company Name'],
            ])
            ->minifiedAjax()
            ->addAction(['width' => '80px'])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }
}
