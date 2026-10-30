@include('purchase::import-purchase.process-form-po', [
    'headingTitle' => __('app.importExcel') . ' ' . __('app.purchaseOrder'),
    'processRoute' => route('purchase-order.po.process', $orderId),
    'backRoute' => route('projects.index'),
    'backButtonText' => __('app.backToProject'),
    'orderId' => $orderId
])
