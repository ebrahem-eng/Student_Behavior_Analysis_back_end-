<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Alert;

class RiskAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->alert->level} Risk Alert] Student Behavior System")
            ->line("An alert has been generated for a student:")
            ->line($this->alert->message)
            ->action('View Alert Details', url('/alerts/' . $this->alert->id))
            ->line('Please take necessary intervention actions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'student_id' => $this->alert->student_id,
            'level' => $this->alert->level,
            'message' => $this->alert->message,
        ];
    }
}
