<?php

namespace App\Mail\Transport;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

class BrevoTransport extends Transport
{
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));

        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $email = [
            'sender' => [
                'email' => env('MAIL_FROM_ADDRESS'),
                'name'  => env('MAIL_FROM_NAME'),
            ],
            'to' => collect($message->getTo())->map(fn($name, $email) => [
                'email' => $email,
                'name'  => $name
            ])->values()->toArray(),
            'subject' => $message->getSubject(),
            'htmlContent' => $message->getBody(),
        ];

        $apiInstance->sendTransacEmail($email);

        return $this->numberOfRecipients($message);
    }
}
