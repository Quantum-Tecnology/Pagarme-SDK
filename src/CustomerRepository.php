<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use Illuminate\Support\Facades\Http;

class CustomerRepository extends BaseRepository
{
    public function __construct()
    {
        $this->urlApi = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(array $data = []): static
    {
        if (0 == count($data)) {
            $data = [
                'name'          => 'Tony Stark',
                'email'         => 'tonystarkk@avengers.com',
                'code'          => 'MY_CUSTOMER_001',
                'document'      => '93095135270',
                'type'          => 'individual',
                'document_type' => 'CPF',
                'gender'        => 'male',
                'address'       => [
                    'line_1'   => '375, Av. General Justo, Centro',
                    'line_2'   => '8º andar',
                    'zip_code' => '20021130',
                    'city'     => 'Rio de Janeiro',
                    'state'    => 'RJ',
                    'country'  => 'BR',
                ],
                'birthdate' => '05/03/1984',
                'phones'    => [
                    'home_phone' => [
                        'country_code' => '55',
                        'area_code'    => '21',
                        'number'       => '000000000',
                    ],
                    'mobile_phone' => [
                        'country_code' => '55',
                        'area_code'    => '21',
                        'number'       => '000000000',
                    ],
                ],
                'metadata' => [
                    'company' => 'Avengers',
                ],
            ];
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->urlApi . '/customers', $data);

        $this->success   = $response->successful();
        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Customer creation failed';
            $this->errors  = (array) ($response->object()->errors ?? []);
            $this->data    = [];

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }
}
