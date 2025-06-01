<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK\Recurrence;

use Illuminate\Support\Facades\Http;
use QuantumTecnology\PagarmeSDK\BaseRepository;
use QuantumTecnology\ValidateTrait\Data;

class PlanRepository extends BaseRepository
{
    public ?string $id = null;

    /**
     * Nome do plano. Max: 64 caracteres.
     */
    public string $name;

    /**
     * Descrição do plano.
     */
    public string $description = '';

    /**
     * Indica se o plano oferece entrega.
     */
    public bool $shippable = false;

    /**
     * Meios de pagamento disponíveis para assinaturas criadas a partir do plano.
     * Valores possíveis: credit_card, boleto ou debit_card. Caso nenhum seja informado, o único meio
     * de pagamento disponível por padrão será credit_card.
     */
    public array $payment_methods = [];

    /**
     * Opções de parcelamento disponíveis para assinaturas criadas a partir do plano.
     * Caso não seja informado, o plano irá disponibilizar apenas assinaturas com pagamentos à vista.
     */
    public array $installments = [];

    /**
     * Valor mínimo em centavos da fatura.
     */
    public int $minimum_price = 0;

    /**
     * Descrição que será exibida no extrato do cliente.
     */
    public string $statement_descriptor = '';
    /**
     * Moeda utilizada no plano.
     * Valores possíveis: BRL, USD, EUR, GBP, JPY, AUD, CAD, CHF, CNY, HKD, NZD, SEK, SGD.
     */
    public string $currency = 'BRL';

    /**
     * Intervalo de recorrência do plano.
     * Valores possíveis: day, week, month, year.
     */
    public string $interval = 'month';

    /**
     * Quantidade de intervalos de recorrência do plano.
     * Exemplo: 1 mês, 2 meses, 3 meses, etc.
     */
    public int $interval_count = 1;
    /**
     * Período de carência do plano.
     * Valores possíveis: 0 a 365 dias.
     */
    public int $trial_period_days = 0;

    /**
     * Tipo de cobrança. Valores possíveis: prepaid, postpaid ou exact_day.
     */
    public string $billing_type = 'prepaid';

    /**
     * Dias disponíveis para cobrança das assinaturas criadas a partir do plano. Deve ser maior ou
     * igual a 1 e menor ou igual a 28. Obrigatório, caso o billing_type seja igual a exact_day.
     */
    public array $billing_days = [];

    /**
     * Itens do plano.
     */
    public array $items = [];

    public function __construct()
    {
        $this->urlApi = sprintf(
            '%s/core/v5/plans',
            config('services.pagarme.url')
        );

        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    /**
     * List plans.
     * Url: https://docs.pagar.me/reference/listar-assinaturas-1.
     */
    public function index(
        ?string $name = null,
        ?string $status = null,
        ?string $createdSince = null,
        ?string $createdUntil = null,
        int $page = 1,
        int $size = 10,
    ): self {
        $query = [];

        if (null !== $name) {
            $query['name'] = $name;
        }

        if (null !== $status) {
            $query['status'] = $status;
        }

        if (null !== $createdSince) {
            $query['created_since'] = $createdSince;
        }

        if (null !== $createdUntil) {
            $query['created_until'] = $createdUntil;
        }

        if ($page > 0) {
            $query['page'] = $page;
        }

        if ($size > 0) {
            $query['size'] = $size;
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->get($this->urlApi, $query);

        if (!$response->successful()) {
            $this->data = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = collect($response->object()->data);

        return $this;
    }

    /**
     * Get plan.
     * Url: https://docs.pagar.me/reference/obter-plano-1.
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

        if (!$response->successful()) {
            $this->http_code = $response->status();
            $this->data      = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = $response->object()->data;

        return $this;
    }

    /**
     * Get plan.
     * Url: https://docs.pagar.me/reference/obter-plano-1.
     */
    public function updateMetadata(?string $id = null): self
    {
        if (null === $this->id || '' === $this->id || '0' === $this->id) {
            $this->id = $id;
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->patch("{$this->urlApi}/{$this->id}/metadata");

        if (!$response->successful()) {
            $this->http_code = $response->status();
            $this->data      = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = $response->object()->data;

        return $this;
    }

    /**
     * Create separate signature.
     * Url: https://docs.pagar.me/reference/criar-plano-1.
     */
    public function store(
        string $name,
        ?string $description = '',
        bool $shippable = false,
        array $paymentMethods = [],
        array $installments = [],
        int $minimumPrice = 0,
        string $statementDescriptor = '',
        string $currency = 'BRL',
        ?string $interval = 'month',
        int $intervalCount = 1,
        int $trialPeriodDays = 0,
        string $billingType = 'prepaid',
        array $billingDays = [],
        array $items = [],
        ?array $metadata = [],

        ?array $data = [],
    ): self {
        if (!in_array($interval, [
            'day',
            'week',
            'month',
            'year',
        ])) {
            $this->errors = ['interval' => 'Invalid interval'];
        }

        if (!in_array($billingType, [
            'prepaid',
            'postpaid',
            'exact_day',
        ])) {
            $this->errors = ['billing_type' => 'Invalid billing type'];
        }

        if (count($this->errors) > 0) {
            $this->data = collect();

            return $this;
        }

        $data                       = new Data($data);
        $data->name                 = $name;
        $data->description          = $description;
        $data->shippable            = $shippable;
        $data->payment_methods      = $paymentMethods;
        $data->minimum_price        = $minimumPrice;
        $data->statement_descriptor = $statementDescriptor;
        $data->billing_type         = $billingType;
        $data->installments         = $installments;
        $data->billing_days         = $billingDays;
        $data->items                = $items;
        $data->trial_period_days    = $trialPeriodDays;
        $data->interval_count       = $intervalCount;
        $data->interval             = $interval;
        $data->metadata             = $metadata ?? [];
        $data->currency             = $currency;

        $data->items = collect($data->items)->map(fn ($item) => collect($item)->only(['id', 'amount', 'quantity'])->toArray())->all();

        $data->payment_methods = collect($data->payment_methods)->map(fn ($method) => mb_strtolower($method))->all();

        $data->interval             = mb_strtolower($data->interval);
        $data->billing_type         = mb_strtolower($data->billing_type);
        $data->currency             = mb_strtoupper($data->currency);
        $data->statement_descriptor = mb_strtoupper($data->statement_descriptor);
        $data->shippable            = (bool) $data->shippable;
        $data->minimum_price        = (int) $data->minimum_price;
        $data->trial_period_days    = (int) $data->trial_period_days;
        $data->interval_count       = (int) $data->interval_count;
        $data->billing_days         = collect($data->billing_days)->map(fn ($day): int => (int) $day)->all();

        $response = Http::withToken($this->authorization, null)
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->urlApi, $data->toArray());

        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->errors = $response->object()->data ?? [];
            $this->data   = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = $response->object()->data;

        return $this;
    }

    /**
     * Create separate signature.
     * Url: https://docs.pagar.me/reference/criar-plano-1.
     */
    public function update(
        string $name,
        ?string $description = '',
        bool $shippable = false,
        array $paymentMethods = [],
        array $installments = [],
        int $minimumPrice = 0,
        string $statementDescriptor = '',
        string $currency = 'BRL',
        ?string $interval = 'month',
        int $intervalCount = 1,
        int $trialPeriodDays = 0,
        string $billingType = 'prepaid',
        array $billingDays = [],
        array $items = [],
        ?array $metadata = [],

        ?array $data = [],
    ): self {
        if (!in_array($interval, [
            'day',
            'week',
            'month',
            'year',
        ])) {
            $this->errors = ['interval' => 'Invalid interval'];
        }

        if (!in_array($billingType, [
            'prepaid',
            'postpaid',
            'exact_day',
        ])) {
            $this->errors = ['billing_type' => 'Invalid billing type'];
        }

        if (count($this->errors) > 0) {
            $this->data = collect();

            return $this;
        }

        $data                       = new Data($data);
        $data->name                 = $name;
        $data->description          = $description;
        $data->shippable            = $shippable;
        $data->payment_methods      = $paymentMethods;
        $data->minimum_price        = $minimumPrice;
        $data->statement_descriptor = $statementDescriptor;
        $data->billing_type         = $billingType;
        $data->installments         = $installments;
        $data->billing_days         = $billingDays;
        $data->items                = $items;
        $data->trial_period_days    = $trialPeriodDays;
        $data->interval_count       = $intervalCount;
        $data->interval             = $interval;
        $data->metadata             = $metadata ?? [];
        $data->currency             = $currency;

        $data->items = collect($data->items)->map(fn ($item) => collect($item)->only(['id', 'amount', 'quantity'])->toArray())->all();

        $data->payment_methods = collect($data->payment_methods)->map(fn ($method) => mb_strtolower($method))->all();

        $data->interval             = mb_strtolower($data->interval);
        $data->billing_type         = mb_strtolower($data->billing_type);
        $data->currency             = mb_strtoupper($data->currency);
        $data->statement_descriptor = mb_strtoupper($data->statement_descriptor);
        $data->shippable            = (bool) $data->shippable;
        $data->minimum_price        = (int) $data->minimum_price;
        $data->trial_period_days    = (int) $data->trial_period_days;
        $data->interval_count       = (int) $data->interval_count;
        $data->billing_days         = collect($data->billing_days)->map(fn ($day): int => (int) $day)->all();

        $response = Http::withToken($this->authorization, null)
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->put($this->urlApi, $data->toArray());

        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->errors = $response->object()->data ?? [];
            $this->data   = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = $response->object()->data;

        return $this;
    }

    /**
     * Get plan.
     * Url: https://docs.pagar.me/reference/obter-plano-1.
     */
    public function destroy(?string $id = null): self
    {
        if (null === $this->id || '' === $this->id || '0' === $this->id) {
            $this->id = $id;
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->delete("{$this->urlApi}/{$this->id}");

        if (!$response->successful()) {
            $this->http_code = $response->status();
            $this->data      = collect();

            return $this;
        }

        $this->success = $response->successful() && $response->object()->success;
        $this->message = $response->object()->message;
        $this->data    = $response->object()->data;

        return $this;
    }
}
