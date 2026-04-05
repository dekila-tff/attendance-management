<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $leaveRequest;

    public function __construct($leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Leave Request Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your leave request has been rejected by your HOD.')
            ->line('Reason: ' . ($this->leaveRequest->rejection_reason ?? 'No reason provided.'))
            ->action('View Leave Requests', url('/leave/history'))
            ->line('Thank you for using the Attendance Management System.');
    }
}
