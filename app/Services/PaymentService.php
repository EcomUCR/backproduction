<?php

namespace App\Services\Payments;

class PaymentService
{
    /**
     * Simula un cargo. En producción integrar con VisaClientContract.
     *
     * @return array{status:string, auth_code:string, transaction_id:string, approved:bool}
     */
    public function charge(int $amountMinorUnits, string $currency, array $cardData = []): array
    {
        // amountMinorUnits: ejemplo 8500 CRC (colones, sin decimales)
        // currency: "CRC" o "USD"

        // Mock sencillo: siempre aprueba.
        return [
            'status'         => 'approved',
            'auth_code'      => 'MOCK-AUTH-'.substr(md5(json_encode($cardData).$amountMinorUnits), 0, 8),
            'transaction_id' => 'MOCK-TXN-'.uniqid(),
            'approved'       => true,
        ];
    }
}
