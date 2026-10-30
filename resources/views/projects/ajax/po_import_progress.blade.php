@include('import.process-form-po', [
    'headingTitle' => __('app.importExcel') . ' ' . __('app.purchaseOrder'),
    'processRoute' => route('purchase-order.po.process', $orderId),
    'backRoute' => $order_type === 'purchase_order'
        ? route('purchase-order.index')
        : route('work_order.work-order'), 
    'backButtonText' => $order_type === 'purchase_order'
        ? __('app.backToPO')
        : __('app.backToWO'),
    'orderId' => $orderId,
    'order_type' => $order_type 
])