<?php

namespace App\Mail\Transport;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport implements TransportInterface
{
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        // Convertir el RawMessage a Email
        $email = MessageConverter::toEmail($message);

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));

        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        $payload = [
            'sender' => [
                'email' => env('MAIL_FROM_ADDRESS'),
                'name' => env('MAIL_FROM_NAME'),
            ],
            'to' => collect($email->getTo())->map(fn($addr) => [
                'email' => $addr->getAddress(),
                'name' => $addr->getName() ?: $addr->getAddress()
            ])->values()->toArray(),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody() ?? $email->getTextBody(),
        ];

        $apiInstance->sendTransacEmail($payload);

        // Symfony espera devolver un SentMessage
        return new SentMessage($message, $envelope ?? Envelope::create($email));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
