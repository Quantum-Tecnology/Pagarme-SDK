<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/cobran%C3%A7as-1.
 */

namespace QuantumTecnology\PagarmeSDK;

class ChargeRepository extends BaseRepository
{
    public function __construct()
    {
        $this->urlApi        = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    /**
     * Consulta uma cobrança pelo id (`ch_...`): status, último erro e
     * transação. É o que diz se um cartão que ficou `pending` foi pago.
     * Url: https://docs.pagar.me/reference/obter-cobran%C3%A7a-1.
     */
    public function find(string $chargeId): static
    {
        $response = $this->request('get')
            ->get($this->urlApi . "/charges/{$chargeId}");

        $this->success   = $response->successful();
        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Charge not found';
            $this->errors  = (array) ($response->object()->errors ?? []);
            $this->data    = [];

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }
}
