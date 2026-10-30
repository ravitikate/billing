<!-- ROW START -->
<div class="row py-0 py-md-0 py-lg-3">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        <div class="card p-3">
            <h5 class="mb-3">{{ __('purchase::modules.vendorPayment.history') }} of work order approval</h5>
            @if ($commentsHistory->isEmpty())
                <div class="alert alert-info">
                    {{ __('app.noComments') }}
                </div>
            @else
                @foreach ($commentsHistory as $item)
                    <div class="card-body border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ $item->user_name }}</h5>
                            <small
                                class="text-muted">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</small>
                        </div>
                        <p class="card-text mt-2">Message: {{ $item->reason }}</p>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</div>
