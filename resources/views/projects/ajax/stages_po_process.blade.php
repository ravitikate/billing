@include('import.process-form-po-stages', [
    'headingTitle' => __('app.importExcel'),
    'processRoute' => route('projects.stages.process', ['orderId' => $purchase_order_id, 'stages' => $stages]),
    'backRoute' => route('purchase-order.index'),
    'backButtonText' => __('app.backToPO'),
    'purchase_order_id' => $purchase_order_id,
    'stages' => $stages
])
