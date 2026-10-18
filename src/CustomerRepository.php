<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use QuantumTecnology\PagarmeSDK\Exceptions\PaymentException;

class CustomerRepository extends BaseRepository
{
    public function __construct()
    {
        $this->urlApi        = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(array $data = []): static
    {
        // Sem dados não há o que criar. Antes era usado um cliente de teste
        // fixo, que ia parar na conta de produção se alguém esquecesse o payload.
        throw_if([] === $data, new PaymentException('Informe os dados para criar o cliente.'));

        $response = $this->request('post')
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
