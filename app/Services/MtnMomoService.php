<?php

namespace App\Services;

use App\Models\Payment;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class MtnMomoService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('services.mtn_momo.base_url', 'https://sandbox.momodeveloper.mtn.com'),
            'timeout'  => 30,
        ]);
    }

    public function requestPayment(Payment $payment, string $phoneNumber): array
    {
        $referenceId = Str::uuid()->toString();
        $token       = $this->getAccessToken();
        $subKey      = config('services.mtn_momo.subscription_key');

        $this->http->post('/collection/v1_0/requesttopay', [
            'headers' => [
                'Authorization'             => "Bearer $token",
                'X-Reference-Id'            => $referenceId,
                'X-Target-Environment'      => config('services.mtn_momo.environment', 'sandbox'),
                'Ocp-Apim-Subscription-Key' => $subKey,
                'Content-Type'              => 'application/json',
            ],
            'json' => [
                'amount'      => (string) intval($payment->amount),
                'currency'    => $payment->currency,
                'externalId'  => $payment->reference,
                'payer'       => ['partyIdType' => 'MSISDN', 'partyId' => ltrim($phoneNumber, '+')],
                'payerMessage'=> 'ImmoStay Réservation',
                'payeeNote'   => 'Paiement ImmoStay',
            ],
        ]);

        return ['status' => 'processing', 'gateway_ref' => $referenceId];
    }

    public function checkStatus(Payment $payment): array
    {
        $token  = $this->getAccessToken();
        $subKey = config('services.mtn_momo.subscription_key');

        // FIX: utilise provider_ref
        $res  = $this->http->get("/collection/v1_0/requesttopay/{$payment->provider_ref}", [
            'headers' => [
                'Authorization'             => "Bearer $token",
                'X-Target-Environment'      => config('services.mtn_momo.environment', 'sandbox'),
                'Ocp-Apim-Subscription-Key' => $subKey,
            ],
        ]);

        $data = json_decode($res->getBody(), true);

        return [
            'status' => match($data['status'] ?? '') {
                'SUCCESSFUL' => 'success', 'FAILED' => 'failed', default => 'processing'
            },
        ];
    }

    private function getAccessToken(): string
    {
        $res = $this->http->post('/collection/token/', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    config('services.mtn_momo.api_user') . ':' . config('services.mtn_momo.api_key')
                ),
                'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key'),
            ],
        ]);
        return json_decode($res->getBody(), true)['access_token'];
    }
}
