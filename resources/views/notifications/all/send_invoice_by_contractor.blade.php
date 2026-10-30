{{-- {{ $notification }} --}}
@php
    $billId   = $notification->data['bill_id'] ?? null;
    $billNo   = $notification->data['bill_no'] ?? '';
    $total    = $notification->data['total'] ?? 0;
    $message  = $notification->data['message'] ?? '';
@endphp

@if ($billId)
    <x-cards.notification
        :notification="$notification"
        :link="route('bills.show', $billId)"
        :image="user()->image_url"
        :title="__('New Bill') . ' - ' . $billNo"
        :text="$message . ' | Total: ₹' . number_format($total, 2)"
        :time="$notification->created_at"
    />
@endif
