<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendInvoiceByContractor extends BaseNotification
{
    use Queueable;

    protected array $dynamicData;

    public function __construct(array $dynamicData)
    {
        $this->dynamicData = $dynamicData;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->dynamicData;
    }
}
