<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveStatusBellNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LeaveRequest $leaveRequest,
        private readonly string $status,
        private readonly string $actionBy,
        private readonly ?string $reason = null
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $leaveType = (string) ($this->leaveRequest->leaveType?->name ?? $this->leaveRequest->leave_type ?? 'Leave');
        $startDate = $this->leaveRequest->start_date ? $this->leaveRequest->start_date->format('Y-m-d') : 'N/A';
        $endDate = $this->leaveRequest->end_date ? $this->leaveRequest->end_date->format('Y-m-d') : 'N/A';
        $status = ucfirst(strtolower(trim($this->status)));
        $reason = trim((string) ($this->reason ?? ''));

        $message = "Your {$leaveType} leave request ({$startDate} to {$endDate}) was {$status} by {$this->actionBy}.";

        if ($reason !== '') {
            $message .= " Reason: {$reason}";
        }

        return [
            'leave_request_id' => $this->leaveRequest->id,
            'status' => $status,
            'action_by' => $this->actionBy,
            'reason' => $reason,
            'message' => $message,
        ];
    }
}
