<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url("https://frontproduction.vercel.app/resetPassword?token={$this->token}&email={$this->email}");

        return (new MailMessage)
                    ->subject('Restablecer contraseña')
                    ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')
                    //->action('Restablecer contraseña', $url)
                    ->line('Si no solicitaste esto, ignora el correo.')
                    ->view('emails.reset-password-html',['url' => $url]);
    }
}
