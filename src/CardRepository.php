<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use QuantumTecnology\PagarmeSDK\Exceptions\PaymentException;

class CardRepository extends BaseRepository
{
    public function __construct()
    {
        $this->urlApi        = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(string $customerId, array $data = []): static
    {
        // Sem dados não há o que criar. Antes era usado um cartão de teste
        // fixo, que ia parar na conta de produção se alguém esquecesse o payload.
        throw_if([] === $data, new PaymentException('Informe os dados para criar o cartão.'));

        $response = $this->request('post')
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
        $response = $this->request('delete')
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
