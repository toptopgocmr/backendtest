<?php

namespace App\Services;

use App\Models\Payment;
use GuzzleHttp\Client;

class AirtelMoneyService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('services.airtel_money.base_url', 'https://openapiuat.airtel.africa'),
            'timeout'  => 30,
        ]);
    }

    public function requestPayment(Payment $payment, string $phoneNumber): array
    {
        $token = $this->getAccessToken();

        $res  = $this->http->post('/merchant/v2/payments/', [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type'  => 'application/json',
                'X-Country'     => 'CG',
                'X-Currency'    => 'XAF',
            ],
            'json' => [
                'reference'   => $payment->reference,
                'subscriber'  => ['country' => 'CG', 'currency' => 'XAF', 'msisdn' => ltrim($phoneNumber, '+')],
                'transaction' => [
                    'amount'   => intval($payment->amount),
                    'country'  => 'CG',
                    'currency' => 'XAF',
                    'id'       => $payment->reference,
                ],
            ],
        ]);

        $data = json_decode($res->getBody(), true);
        return [
            'status'      => 'processing',
            'gateway_ref' => $data['data']['transaction']['id'] ?? $payment->reference,
        ];
    }

    public function checkStatus(Payment $payment): array
    {
        $token = $this->getAccessToken();

        // FIX: utilise provider_ref
        $res  = $this->http->get("/standard/v1/payments/{$payment->provider_ref}", [
            'headers' => ['Authorization' => "Bearer $token", 'X-Country' => 'CG', 'X-Currency' => 'XAF'],
        ]);

        $data = json_decode($res->getBody(), true);
        $s    = $data['data']['transaction']['status'] ?? '';

        return ['status' => $s === 'TS' ? 'success' : ($s === 'TF' ? 'failed' : 'processing')];
    }

    private function getAccessToken(): string
    {
        $res = $this->http->post('/auth/oauth2/token', [
            'json' => [
                'client_id'     => config('services.airtel_money.client_id'),
                'client_secret' => config('services.airtel_money.client_secret'),
                'grant_type'    => 'client_credentials',
            ],
        ]);
        return json_decode($res->getBody(), true)['access_token'];
    }
}
