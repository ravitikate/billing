<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BillApproveByFinalAuthority extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected array $dynamicData;

    public function __construct(array $data)
    {
        $this->dynamicData = $data;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // can also add 'mail'
    }

    public function toArray(object $notifiable): array
    {
        return $this->dynamicData;
    }
}
