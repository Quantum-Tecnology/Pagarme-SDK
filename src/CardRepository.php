<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use Illuminate\Support\Facades\Http;

class CardRepository extends BaseRepository
{
    public function __construct()
    {
        $this->urlApi = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(string $customerId, array $data = []): static
    {
        if (0 == count($data)) {
            $data = [
                'number'          => '4000000000000010',
                'holder_name'     => 'Tony Stark',
                'holder_document' => '93095135270',
                'exp_month'       => 1,
                'exp_year'        => 30,
                'cvv'             => '351',
                'brand'           => 'Mastercard',
                'billing_address' => [
                    'line_1'   => '375, Av. General Osorio, Centro',
                    'line_2'   => '7º Andar',
                    'zip_code' => '220000111',
                    'city'     => 'Rio de Janeiro',
                    'state'    => 'RJ',
                    'country'  => 'BR',
                ],
                'options' => [
                    'verify_card' => true,
                ],
            ];
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->urlApi . "/customers/{$customerId}/cards", $data);

        $this->success   = $response->successful();
        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Card creation failed';
            $this->errors  = (array) ($response->object()->errors ?? []);

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }

    public function destroy(string $customerId, string $cardId): static
    {
        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->delete($this->urlApi . "/customers/{$customerId}/cards/{$cardId}");

        $this->success   = $response->successful();
        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Card deletion failed';
            $this->errors  = (array) ($response->object()->errors ?? []);

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }
}
