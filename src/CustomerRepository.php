<?php

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumCode\PagarmeSDK;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class CustomerRepository
{
    private string $url;
    private string $token;

    public function __construct()
    {
        $this->url   = config('services.pagar_me.url');
        $this->token = base64_encode(config('services.pagar_me.access_token').':');
    }

    public function create(array $data = [])
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

        $response = Http::withToken($this->token, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->url.'/customers', $data);

        if (!$response->successful()) {
            return new Collection();
        }

        return $this->map($response->object());
    }

    public function map(object|array $data)
    {
        foreach ($data as $index => $attribute) {
            if (is_array($attribute)) {
                $data->$index = collect($attribute);
            }
        }

        return $data;
    }
}
