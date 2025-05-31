<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use Illuminate\Support\Facades\Http;

class CardRepository
{
    public bool $success = false;
    public string $message;
    public array $errors = [];
    public array | object $data;
    private string $url;
    private string $token;

    public function __construct()
    {
        $this->url   = config('services.pagarme.url');
        $this->token = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(string $customerId, array $data = [])
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
                // 'label'           => 'Sua bandeira',
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

        $response = Http::withToken($this->token, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            // ->dd()
            ->post($this->url . "/customers/{$customerId}/cards", $data);

        $this->success = $response->successful();

        if (!$response->successful()) {
            $this->message = $response->object()->message;
            $this->errors  = (array) $response->object()->errors;

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }

    public function destroy(string $customerId, string $cardId)
    {
        $response = Http::withToken($this->token, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            // ->dd()
            ->delete($this->url . "/customers/{$customerId}/cards/{$cardId}");

        if (!$response->successful()) {
            return false;
        }

        return collect($this->map($response->object()));
    }

    public function map(object | array $data)
    {
        foreach ($data as $index => $attribute) {
            if (is_array($attribute)) {
                $data->$index = collect($attribute);
            }
        }

        return $data;
    }
}
