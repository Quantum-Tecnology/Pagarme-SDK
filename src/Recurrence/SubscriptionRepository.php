<?php

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace GustavoSantarosa\PagarmeSDK\Recurrence;

use App\Repositories\BaseRepository;
use GustavoSantarosa\ValidateTrait\Data;
use Illuminate\Support\Facades\Http;

class SubscriptionRepository extends BaseRepository
{
    public ?string $id = null;

    public function __construct()
    {
        $this->urlApi = sprintf(
            '%s/core/v5/subscriptions',
            config('services.pagarme.url')
        );

        $this->authorization = base64_encode(config('services.pagarme.access_token').':');
    }

    /**
     * List subscriptions.
     * Url: https://docs.pagar.me/reference/listar-assinaturas-1.
     */
    public function index(): self
    {
        // TODO - Implementar paginação
        if (true) {
            $this->urlApi .= '?page=1&size=10';
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->get($this->urlApi);

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
     * Get subscription.
     * Url: https://docs.pagar.me/reference/obter-assinatura-1.
     */
    public function show(?string $id = null): self
    {
        if (!$this->id) {
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
     * Cancel subscription
     * Url: https://docs.pagar.me/reference/cancelar-assinatura-1.
     */
    public function destroy(
        ?string $id = null,
        ?bool $cancel_pending = true,
    ): self {
        if (!$this->id) {
            $this->id = $id;
        }

        $data = new Data([
            'cancel_pending_invoices' => $cancel_pending,
        ]);

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->delete("{$this->urlApi}/{$this->id}", $data->toArray());

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
     * Url: https://docs.pagar.me/reference/criar-assinatura-avulsa.
     */
    public function store(
        ?string $custumer_id = null,
        ?array $custumer = null,
        ?string $payment_method = 'credit_card',
        ?string $interval = 'month',
        ?string $currency = 'BRL',
        ?string $description = '',
        ?string $minimum_price = null,
        ?int $interval_count = 1,
        ?string $billing_type = 'prepaid',
        ?int $installments = 1,
        ?array $pricing_scheme = ['scheme_type' => 'unit'],
        ?int $quantity = null,
        ?array $increments = [],
        ?array $items = [],
        ?string $metadata = null,
        ?string $card = null,
        ?array $data = [],
    ): self {
        if (null === $description && count($items) < 1) {
            $this->errors = ['description' => 'Description is required when items is empty'];
        }

        $this->itemsValidation($items);
        $this->paymentMethodValidation($payment_method, $card);
        $this->pricingSchemeValidation($pricing_scheme, $quantity);
        $this->incrementValidation($increments);
        $this->custumerValidation($custumer, $custumer_id);

        if (!in_array($interval, [
            'day',
            'week',
            'month',
            'year',
        ])) {
            $this->errors = ['interval' => 'Invalid interval'];
        }

        if (!in_array($billing_type, [
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

        $data                 = new Data($data);
        $data->custumer_id    = $custumer_id;
        $data->custumer       = $custumer;
        $data->payment_method = $payment_method;
        $data->interval       = $interval;
        $data->minimum_price  = $minimum_price;
        $data->interval_count = $interval_count;
        $data->billing_type   = $billing_type;
        $data->installments   = $installments;
        $data->pricing_scheme = $pricing_scheme;
        $data->quantity       = $quantity;
        $data->metadata       = $metadata;
        $data->currency       = $currency;
        $data->increments     = $increments;
        $data->items          = [['pricing_scheme' => ['scheme_type' => 'Unit']]];

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

    private function itemsValidation(array $items): void
    {
        collect($items)->each(function ($item, $key) {
            if (!isset($item['description'])) {
                $this->errors = ['description' => 'Description is required on item '.$key];
            }

            if (!isset($item['quantity'])) {
                $this->errors = ['quantity' => 'Quantity is required on item '.$key];
            }

            if (!in_array($item['status'], [
                'active',
                'inactive',
                'deleted',
            ])) {
                $this->errors = ['status' => 'Invalid status on item '.$key];
            }
        });
    }

    private function paymentMethodValidation(
        string $payment_method,
        ?array $card = null,
    ): void {
        if (!in_array($payment_method, [
            'credit_card',
            'debit_card',
            'boleto',
        ])) {
            $this->errors = ['payment_method' => 'Invalid payment method'];
        }

        if (null !== $card && in_array($payment_method, [
            'credit_card',
            'debit_card',
        ])) {
            $this->errors = ['card' => 'Card is required on payment method credit_card or debit_card'];
        }

        if (in_array($payment_method, [
            'credit_card',
            'debit_card',
        ]) && null === $card['number'] && (null === $card['id'] || null === $card['token'])) {
            $this->errors = ['card' => 'Card number or card_id or card_token is required on payment method credit_card or debit_card'];
        }
    }

    private function pricingSchemeValidation(
        array $pricing_scheme,
        string $quantity,
    ): void {
        if (!in_array($pricing_scheme['scheme_type'], [
            'unit',
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors = ['pricing_scheme' => 'Invalid pricing scheme'];
        }

        if ('unit' === $pricing_scheme['scheme_type'] && null == $pricing_scheme['price']) {
            $this->errors = ['price' => 'Price is required on pricing scheme unit'];
        }

        if (null !== $pricing_scheme['price_brackets'] && in_array($pricing_scheme['scheme_type'], [
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors = ['price_brackets' => 'Price brackets is required on pricing scheme package, tier and volume'];
        }

        if (null !== $pricing_scheme['price'] && in_array($pricing_scheme['scheme_type'], [
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors = ['price' => 'Price is not required on pricing scheme package, tier and volume'];
        }

        if ('unit' === $pricing_scheme['scheme_type'] && null === $quantity) {
            $this->errors = ['quantity' => 'Quantity is required on pricing scheme unit'];
        }
    }

    private function incrementValidation(array $increments): void
    {
        collect($increments)->each(function ($increment, $key) {
            if (isset($increment['value']) && is_int($increment['value'])) {
                $this->errors = ['value' => 'Value must be an integer on increment '.$key];
            }

            if (isset($increment['cycles']) && is_string($increment['cycles'])) {
                $this->errors = ['cycles' => 'Cycles must be an string on increment '.$key];
            }

            if (isset($increment['increment_type']) && !in_array($increment['increment_type'], [
                'percentage',
                'flat',
            ])) {
                $this->errors = ['increment_type' => 'Invalid increment type on increment '.$key];
            }
        });
    }

    private function custumerValidation(
        ?array $custumer,
        ?string $custumer_id,
    ): void {
        if (null === $custumer && null === $custumer_id) {
            $this->errors = ['custumer' => 'Custumer or custumer_id is required'];
        }

        if (null !== $custumer && null !== $custumer_id) {
            $this->errors = ['custumer' => 'Custumer and custumer_id cannot be used together'];
        }

        if (null !== $custumer && !isset($custumer['name'])) {
            $this->errors = ['name' => 'Name is required on custumer'];
        }

        if (null !== $custumer && isset($custumer['email']) && strlen($custumer['email']) > 64) {
            $this->errors = ['email' => 'Email must be less than 64 characters'];
        }

        if (null !== $custumer && isset($custumer['code']) && strlen($custumer['code']) > 52) {
            $this->errors = ['code' => 'Code must be less than 52 characters'];
        }

        if (null !== $custumer && isset($custumer['document_type']) && in_array($custumer['document_type'], [
            'cpf',
            'cnpj',
            'passport',
        ])
        ) {
            $this->errors = ['document_type' => 'Invalid document type on custumer'];
        }

        if (null !== $custumer && isset($custumer['document_type']) && !isset($custumer['document'])) {
            $this->errors = ['document' => 'Document number is required on custumer when document type is set'];
        }

        if (null !== $custumer && isset($custumer['document']) && 'passport' === $custumer['document'] && strlen($custumer['document']) > 50) {
            $this->errors = ['document' => 'Document must be less than 50 characters when document type is passport'];
        }

        if (null !== $custumer && isset($custumer['document']) && 'passport' !== $custumer['document'] && strlen($custumer['document']) > 16) {
            $this->errors = ['document' => 'Document must be less than 16 characters when document type is cpf or cnpj'];
        }

        if (null !== $custumer && isset($custumer['gender']) && !in_array($custumer['gender'], [
            'male',
            'female',
        ])) {
            $this->errors = ['gender' => 'Invalid gender on custumer'];
        }
    }
}
