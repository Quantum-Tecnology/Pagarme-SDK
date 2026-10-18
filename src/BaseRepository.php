<?php

declare(strict_types = 1);

namespace QuantumTecnology\PagarmeSDK;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class BaseRepository
{
    public bool $success              = false;
    public int $http_code             = 0;
    public string $message            = 'not found';
    public array | object $errors     = [];
    public array | object $data       = [];
    protected string $urlApi          = 'localhost';
    protected string $authorization   = '';
    protected ?string $idempotencyKey = null;

    /**
     * Chave enviada no header `Idempotency-Key`. Com ela o Pagar.me devolve o
     * mesmo resultado para a mesma chave, então a requisição pode ser
     * repetida com segurança. Sem ela, um POST nunca é repetido.
     */
    public function withIdempotencyKey(string $key): static
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Usa outra chave secreta em vez de `services.pagarme.access_token`
     * (ex.: chave guardada no banco e trocada em runtime).
     */
    public function withAccessToken(string $accessToken): static
    {
        $this->authorization = base64_encode($accessToken . ':');

        return $this;
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

    /**
     * Requisição autenticada, com a política de repetição do pacote.
     *
     * Só repete o que pode ser repetido sem efeito colateral: GET, PUT, PATCH
     * e DELETE (idempotentes pela semântica HTTP), e POST somente quando há
     * `Idempotency-Key`. Mesmo assim, só em falha de conexão ou erro 5xx:
     * erro 4xx é determinístico e repetir não muda o resultado.
     *
     * Repetir um POST sem chave é o que cobra o cliente duas vezes: o
     * timeout pode acontecer DEPOIS de o Pagar.me criar o pedido.
     */
    protected function request(string $method): PendingRequest
    {
        $request = Http::withToken($this->authorization, 'Basic')
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.pagarme.timeout', 30));

        if (null !== $this->idempotencyKey) {
            $request = $request->withHeaders(['Idempotency-Key' => $this->idempotencyKey]);
        }

        $retryable = 'post' !== mb_strtolower($method) || null !== $this->idempotencyKey;

        if (!$retryable) {
            return $request;
        }

        return $request->retry(
            3,
            2000,
            static fn (Throwable $e): bool => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()),
            throw: false,
        );
    }
}
