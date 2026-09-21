<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/listar-faturas-1.
 */

namespace QuantumTecnology\PagarmeSDK\Recurrence;

use Illuminate\Support\Facades\Http;
use QuantumTecnology\PagarmeSDK\BaseRepository;

/**
 * Faturas de assinatura.
 *
 * ⭐ `GET /subscriptions/{id}` NÃO traz o histórico de faturas: devolve apenas
 * o ciclo corrente. Quem precisa saber o que o cliente já pagou — conciliação,
 * tempo de casa, receita observada — tem de vir aqui, que é outro recurso da
 * API.
 *
 * Foi a ausência disto que deixou um serviço da casa sem como responder "há
 * quantos meses este cliente paga?": o dado existia no gateway e não havia
 * chamada para buscá-lo.
 */
class InvoiceRepository extends BaseRepository
{
    public ?string $id = null;

    public function __construct()
    {
        $this->urlApi = config('services.pagarme.url') . '/invoices';

        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    /**
     * Lista faturas, opcionalmente filtradas.
     *
     * Url: https://docs.pagar.me/reference/listar-faturas-1.
     *
     * ⚠️ A API pagina em 10 por padrão e aceita no máximo 100 (`size`). Uma
     * assinatura antiga tem mais faturas que isso, então quem precisa do
     * histórico COMPLETO deve usar `allBySubscription()` — pedir só a primeira
     * página devolve um recorte silencioso, sem nada indicando que faltou.
     *
     * @param array<string, mixed> $filters subscription_id, status, page, size,
     *                                      customer_id, created_since, created_until
     */
    public function index(array $filters = []): self
    {
        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->get($this->urlApi, array_filter($filters, static fn ($v): bool => null !== $v && '' !== $v));

        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Request failed';
            $this->errors  = (array) ($response->object()->errors ?? []);
            $this->data    = collect();

            return $this;
        }

        $this->success = true;
        $this->data    = $this->map($response->object());

        return $this;
    }

    /**
     * Obtém uma fatura.
     * Url: https://docs.pagar.me/reference/obter-fatura-1.
     */
    public function show(?string $id = null): self
    {
        if (null === $this->id || '' === $this->id || '0' === $this->id) {
            $this->id = $id;
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->get("{$this->urlApi}/{$this->id}");

        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Request failed';
            $this->errors  = (array) ($response->object()->errors ?? []);
            $this->data    = collect();

            return $this;
        }

        $this->success = true;
        $this->data    = $this->map($response->object());

        return $this;
    }

    /**
     * Todas as faturas de uma assinatura, percorrendo a paginação.
     *
     * ⭐ Existe porque `index()` sozinho ENGANA: devolve a primeira página como
     * se fosse o conjunto, e quem conta faturas para calcular tempo de casa ou
     * receita chegaria a um número menor que o real sem perceber.
     *
     * `$maxPages` é uma trava de segurança, não um limite de negócio: sem ela,
     * uma resposta inesperada da API (paginação que nunca termina) viraria
     * laço infinito dentro de um job.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function allBySubscription(string $subscriptionId, int $maxPages = 20): \Illuminate\Support\Collection
    {
        $todas = collect();
        $page  = 1;
        $size  = 100;

        do {
            $this->index([
                'subscription_id' => $subscriptionId,
                'page'            => $page,
                'size'            => $size,
            ]);

            if (!$this->success) {
                // Falha no meio da paginação: devolve o que veio e sinaliza em
                // `success`, para quem chamou decidir se o parcial serve.
                return $todas;
            }

            $lote  = collect($this->data->data ?? []);
            $todas = $todas->concat($lote);

            ++$page;
        } while ($lote->count() === $size && $page <= $maxPages);

        $this->success = true;

        return $todas;
    }
}
