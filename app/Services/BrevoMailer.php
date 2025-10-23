<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class BrevoMailer
{
    public static function send($to, $subject, $html)
    {
        try {
            $config = Configuration::getDefaultConfiguration()
                ->setApiKey('api-key', env('BREVO_API_KEY'));

            $apiInstance = new TransactionalEmailsApi(new Client(), $config);

            // Crear instancia del objeto de email (no un array)
            $email = new SendSmtpEmail([
                'sender' => [
                    'email' => env('MAIL_FROM_ADDRESS', 'no-reply@tukishopcr.com'),
                    'name'  => env('MAIL_FROM_NAME', 'TukiShop CR'),
                ],
                'to' => [
                    ['email' => $to],
                ],
                'subject' => $subject,
                'htmlContent' => $html,
            ]);

            // Enviar el correo
            $response = $apiInstance->sendTransacEmail($email);

            return $response;

        } catch (\Exception $e) {
            \Log::error('Error al enviar correo con Brevo: ' . $e->getMessage());
            return false;
        }
    }
}
