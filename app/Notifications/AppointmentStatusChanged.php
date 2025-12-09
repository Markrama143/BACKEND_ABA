<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentStatusChanged extends Notification
{
    use Queueable;

    public $status;
    public $appointmentId;

    public function __construct($status, $appointmentId)
    {
        $this->status = $status;
        $this->appointmentId = $appointmentId;
    }

    public function via($notifiable)
    {
        return ['database']; // Important: Store in database
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Appointment Update',
            'body' => 'Your appointment is now ' . $this->status,
            'appointment_id' => $this->appointmentId,
            'type' => 'status_update'
        ];
    }
}