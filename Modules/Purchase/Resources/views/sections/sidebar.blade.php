@php
    $purchaseViewVendorPermission = user()->permission('view_vendor');
    $purchaseViewOrderPermission = user()->permission('view_purchase_order');
    $purchaseViewBillPermission = user()->permission('view_bill');
    $purchaseViewCreditPermission = user()->permission('view_vendor_credit');
    $purchaseViewInventoryPermission = user()->permission('view_inventory');
    $purchaseViewOrderReportPermission = user()->permission('view_order_report');
    $purchaseViewPaymentPermission = user()->permission('view_vendor_payment');

    $item_approval = user()->permission('item_approval');
    $bill_approval = user()->permission('bill_approval');
     $delay_bill_show_sidebar = user()->permission('delay_bill_show_sidebar');
    $abstract_approval = user()->permission('abstract_approval');
    $work_order = user()->permission('work_order');
@endphp
@if (in_array(\Modules\Purchase\Entities\PurchaseManagementSetting::MODULE_NAME, user_modules()) && ($purchaseViewVendorPermission != 'none' || $purchaseViewOrderPermission != 'none' || $purchaseViewBillPermission != 'none'
|| $purchaseViewCreditPermission != 'none' || $purchaseViewInventoryPermission != 'none' || $purchaseViewOrderReportPermission != 'none' || $purchaseViewPaymentPermission != 'none'))

    <x-menu-item icon="wallet" :text="__('app.contract')" :addon="App::environment('demo')">
        <x-slot name="iconPath">
         <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3.854 2.146a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 3.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 7.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0"/>

        </x-slot>



        <div class="accordionItemContent pb-2">
            <x-sub-menu-item :link="route('purchase-order.index')"
                            :text="__('purchase::app.menu.purchaseOrder')"
                            :permission="($purchaseViewOrderPermission != 'none' && $purchaseViewOrderPermission != '')"
            />
            <!-- NAV ITEM - Work ORDERS -->
            <x-sub-menu-item :link="route('work_order.work-order')"
                            :text="__('purchase::app.menu.workOrder')"
                            :permission="($work_order != 'none' && $work_order != '')"
            />
        </div>
    </x-menu-item>
    <x-menu-item icon="wallet" :text="__('app.billing')" :addon="App::environment('demo')">
        <x-slot name="iconPath">
             <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/>
  <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>

        </x-slot>



        <div class="accordionItemContent pb-2">
            <!-- NAV ITEM - BILLS -->
            <x-sub-menu-item :link="route('bills.index')"
                            :text="__('purchase::app.menu.billss')"
                            :permission="($purchaseViewBillPermission != 'none' && $purchaseViewBillPermission != '')"
            />
            <!-- NAV ITEM - Abstract -->
            <x-sub-menu-item :link="route('vendor-credits.show-abstract')"
                            :text="__('purchase::app.menu.billAbstract')"
                            :permission="($abstract_approval != 'none' && $abstract_approval != '')"
            />
            <!-- NAV ITEM - Bill Approval -->
            <x-sub-menu-item :link="route('bills.bill-approval')"
            :text="__('purchase::app.menu.approveBill')"
            :permission="($bill_approval != 'none' && $bill_approval != '')"
            />

            <!-- NAV ITEM - Bill Approval -->
            <x-sub-menu-item :link="route('bills.bill-delay')"
            :text="__('purchase::app.menu.delay')"
            :permission="($delay_bill_show_sidebar != 'none' && $bill_approval != '')"
            />

            <x-sub-menu-item :link="route('bills.delay-details')"
            :text="__('purchase::app.menu.delayDetails')"
            :permission="($delay_bill_show_sidebar != 'none' && $bill_approval != '')"
            />

            <!-- NAV ITEM - Items Approval -->
            <x-sub-menu-item :link="route('vendor-credits.show-bills')"
                            :text="__('purchase::app.menu.billApprove')"
                            :permission="($item_approval != 'none' && $item_approval != '')"
            />

                    </div>

    </x-menu-item>

     <x-menu-item icon="wallet" :text="__('purchase::app.menu.vendor')" :addon="App::environment('demo')">
        <x-slot name="iconPath">
              <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
  <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5v10.795a4.2 4.2 0 0 0-.776-.492C11.392 12.387 10.063 12 8 12s-3.392.387-4.224.803a4.2 4.2 0 0 0-.776.492z"/>

        </x-slot>



        <div class="accordionItemContent pb-2">
            <!-- NAV ITEM - VENDORS -->
            <x-sub-menu-item :link="route('vendors.index')"
                            :text="__('purchase::app.menu.vendor')"
                            :permission="($purchaseViewVendorPermission != 'none' && $purchaseViewVendorPermission != '')"
            />
            <!-- NAV ITEM - PRODUCTS -->
            @if (in_array('products', user_modules()) && $sidebarUserPermissions['view_product'] != 5 && $sidebarUserPermissions['view_product'] != 'none')
               <x-sub-menu-item :link="route('purchase-products.index')" :text="__('purchase::app.menu.products')" />
            @endif
            <!--NAV ITEM - PAYMENTS -->
            <x-sub-menu-item :link="route('vendor-payments.index')"
                            :text="__('purchase::app.purchaseOrder.vendorPayments')"
                            :permission="($purchaseViewPaymentPermission != 'none' && $purchaseViewPaymentPermission != '')"
            />
            <x-sub-menu-item :link="route('vendor-credits.index')"
                            :text="__('purchase::app.menu.vendorCredits')"
                            :permission="($purchaseViewCreditPermission != 'none' && $purchaseViewCreditPermission != '')"
            />
            <x-sub-menu-item :link="route('purchase-inventory.index')" :text="__('purchase::app.menu.inventory')"
            :permission="($purchaseViewInventoryPermission != 'none' && $purchaseViewInventoryPermission != '')"
            />
            <!-- NAV ITEM - REPORTS -->
            <x-sub-menu-item :link="route('reports.index')" :text="__('purchase::app.menu.reports')"
            :permission="($purchaseViewOrderReportPermission != 'none' && $purchaseViewOrderReportPermission != '')"
            />

        </div>

    </x-menu-item>

@endif
