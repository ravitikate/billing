@include('import.process-form-parameter', [
    'headingTitle' => __('app.importExcel') . ' ' . __('app.budgetHeads'),
    'processRoute' => route('projects.budgetheads.process', $budgetHeadsId),
    'backRoute' => route('budget.index'),
    'backButtonText' => __('app.backToProject'),
    'budgetHeadsId' => $budgetHeadsId
])
