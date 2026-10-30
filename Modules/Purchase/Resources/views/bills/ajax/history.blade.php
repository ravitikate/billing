<!-- ROW START -->
<div class="row py-0 py-md-0 py-lg-3">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        <div class="card p-3">
            <h5 class="mb-3">{{ __('purchase::modules.vendorPayment.history') }} of Bill</h5>

            @forelse ($commentsHistory as $item)
                @if (empty($item->bypass))
                    @continue
                @endif

                <div class="card-body border mb-3">
                    <div class="d-flex gap-5 align-items-center">
                        <h5 class="card-title mb-0">{{ $item->user_name }}</h5>
                        <small class="text-muted">
                            ( Date:- {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i a') }} )
                            @if ($item->approval_status == 3)
                                - <span class="text-danger">{{ __('app.rejected') }}</span> <i class="fa fa-times text-danger"></i>
                            @elseif($item->approval_status == 2)
                                - <span class="text-success">{{ __('app.approved') }}</span> <i class="fa fa-check text-success"></i>
                            @elseif($item->approval_status == null)
                                -  <span class="text-warning">{{ __('app.bypass') }}</span> <i class="fa fa-exclamation-triangle text-warning"></i>
                            @endif
                        </small>
                    </div>
                    @if (!empty($item->message))
                        <p class="card-text mt-2">Message: {{ $item->message }}</p>
                    @endif
                </div>
            @empty
                <div class="alert alert-info">
                    {{ __('app.noComments') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
<style>
    .gap-5 {
        gap: 1rem !important;
    }
</style>
