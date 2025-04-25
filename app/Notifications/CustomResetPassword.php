<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('🔐 Recupera tu contraseña')
            ->greeting('¡Hola! te saluda La Campaña Food Service.')
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')
            ->line('Si no hiciste esta solicitud, puedes ignorar este mensaje. De ser necesario, puedes comunicarte con un administrador si has notado alguna actividad sospechosa en tu cuenta.')
            ->salutation('Saludos, La Campaña Food Service')
            ->line('Este enlace expirará en 60 minutos.')
            ->action('Restablecer contraseña', $url);
    }
}