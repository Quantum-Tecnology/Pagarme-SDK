<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-assinatura-avulsa.
 */

namespace QuantumTecnology\PagarmeSDK\Recurrence;

use Illuminate\Support\Facades\Http;
use QuantumTecnology\PagarmeSDK\BaseRepository;
use QuantumTecnology\ValidateTrait\Data;

class SubscriptionRepository extends BaseRepository
{
    public ?string $id = null;

    public function __construct()
    {
        $this->urlApi = sprintf(
            '%s/core/v5/subscriptions',
            config('services.pagarme.url')
        );

        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    /**
     * List subscriptions.
     * Url: https://docs.pagar.me/reference/listar-assinaturas-1.
     */
    public function index(): self
    {
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
     * Cancel subscription.
     * Url: https://docs.pagar.me/reference/cancelar-assinatura-1.
     */
    public function destroy(
        ?string $id = null,
        ?bool $cancel_pending = true,
    ): self {
        if (null === $this->id || '' === $this->id || '0' === $this->id) {
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
     * Create subscription.
     * Url: https://docs.pagar.me/reference/criar-assinatura-avulsa.
     */
    public function store(
        ?string $customer_id = null,
        ?array $customer = null,
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
        null|string|array $card = null,
        ?array $data = [],
    ): self {
        if (null === $description && count($items) < 1) {
            $this->errors['description'] = 'Description is required when items is empty';
        }

        $this->itemsValidation($items);
        $this->paymentMethodValidation($payment_method, $card);
        $this->pricingSchemeValidation($pricing_scheme, $quantity);
        $this->incrementValidation($increments);
        $this->customerValidation(
            customer: $customer,
            customer_id: $customer_id
        );

        if (!in_array($interval, [
            'day',
            'week',
            'month',
            'year',
        ])) {
            $this->errors['interval'] = 'Invalid interval';
        }

        if (!in_array($billing_type, [
            'prepaid',
            'postpaid',
            'exact_day',
        ])) {
            $this->errors['billing_type'] = 'Invalid billing type';
        }

        if (count($this->errors) > 0) {
            $this->data = collect();

            return $this;
        }

        $data                 = new Data($data);
        $data->customer_id    = $customer_id;
        $data->customer       = $customer;
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
        $data->items          = $items;

        // Handle card: string = card_id, array = full card details
        if (is_string($card)) {
            $data->card_id = $card;
        } elseif (is_array($card)) {
            $data->card = $card;
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->urlApi, $data->toArray());

        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->errors = (array) ($response->object()->data ?? []);
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
        collect($items)->each(function ($item, string $key): void {
            if (!isset($item['description'])) {
                $this->errors["items.{$key}.description"] = 'Description is required on item ' . $key;
            }

            if (!isset($item['quantity'])) {
                $this->errors["items.{$key}.quantity"] = 'Quantity is required on item ' . $key;
            }

            if (isset($item['status']) && !in_array($item['status'], [
                'active',
                'inactive',
                'deleted',
            ])) {
                $this->errors["items.{$key}.status"] = 'Invalid status on item ' . $key;
            }
        });
    }

    private function paymentMethodValidation(
        string $payment_method,
        null|string|array $card = null,
    ): void {
        if (!in_array($payment_method, [
            'credit_card',
            'debit_card',
            'boleto',
        ])) {
            $this->errors['payment_method'] = 'Invalid payment method';

            return;
        }

        // Card is required for credit_card and debit_card
        if (null === $card && in_array($payment_method, [
            'credit_card',
            'debit_card',
        ])) {
            $this->errors['card'] = 'Card is required for credit_card or debit_card payment methods';

            return;
        }

        // String card = card_id or card_token, no further validation needed
        if (is_string($card)) {
            return;
        }

        // Array card must have number, card_id, or card_token
        if (is_array($card) && !isset($card['number']) && !isset($card['card_id']) && !isset($card['card_token'])) {
            $this->errors['card'] = 'Card number, card_id, or card_token is required';
        }
    }

    private function pricingSchemeValidation(
        array $pricing_scheme,
        ?int $quantity,
    ): void {
        if (!in_array($pricing_scheme['scheme_type'] ?? null, [
            'unit',
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors['pricing_scheme'] = 'Invalid pricing scheme';
        }

        if ('unit' === ($pricing_scheme['scheme_type'] ?? null) && empty($pricing_scheme['price'])) {
            $this->errors['price'] = 'Price is required on pricing scheme unit';
        }

        if (!isset($pricing_scheme['price_brackets']) && in_array($pricing_scheme['scheme_type'] ?? null, [
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors['price_brackets'] = 'Price brackets is required on pricing scheme package, tier and volume';
        }

        if (isset($pricing_scheme['price']) && in_array($pricing_scheme['scheme_type'] ?? null, [
            'package',
            'tier',
            'volume',
        ])) {
            $this->errors['price_conflict'] = 'Price is not allowed on pricing scheme package, tier and volume';
        }

        if ('unit' === ($pricing_scheme['scheme_type'] ?? null) && null === $quantity) {
            $this->errors['quantity'] = 'Quantity is required on pricing scheme unit';
        }
    }

    private function incrementValidation(array $increments): void
    {
        collect($increments)->each(function ($increment, string $key): void {
            if (isset($increment['value']) && !is_int($increment['value'])) {
                $this->errors["increments.{$key}.value"] = 'Value must be an integer on increment ' . $key;
            }

            if (isset($increment['cycles']) && !is_int($increment['cycles'])) {
                $this->errors["increments.{$key}.cycles"] = 'Cycles must be an integer on increment ' . $key;
            }

            if (isset($increment['increment_type']) && !in_array($increment['increment_type'], [
                'percentage',
                'flat',
            ])) {
                $this->errors["increments.{$key}.increment_type"] = 'Invalid increment type on increment ' . $key;
            }
        });
    }

    private function customerValidation(
        ?array $customer,
        ?string $customer_id,
    ): void {
        if (null === $customer && null === $customer_id) {
            $this->errors['customer'] = 'Customer or customer_id is required';
        }

        if (null !== $customer && null !== $customer_id) {
            $this->errors['customer'] = 'Customer and customer_id cannot be used together';
        }

        if (null !== $customer && !isset($customer['name'])) {
            $this->errors['customer.name'] = 'Name is required on customer';
        }

        if (null !== $customer && isset($customer['email']) && mb_strlen($customer['email']) > 64) {
            $this->errors['customer.email'] = 'Email must be less than 64 characters';
        }

        if (null !== $customer && isset($customer['code']) && mb_strlen($customer['code']) > 52) {
            $this->errors['customer.code'] = 'Code must be less than 52 characters';
        }

        if (null !== $customer && isset($customer['document_type']) && !in_array($customer['document_type'], [
            'cpf',
            'cnpj',
            'passport',
        ])
        ) {
            $this->errors['customer.document_type'] = 'Invalid document type on customer';
        }

        if (null !== $customer && isset($customer['document_type']) && !isset($customer['document'])) {
            $this->errors['customer.document'] = 'Document number is required on customer when document type is set';
        }

        if (null !== $customer && isset($customer['document_type']) && 'passport' === ($customer['document_type'] ?? null) && isset($customer['document']) && mb_strlen($customer['document']) > 50) {
            $this->errors['customer.document_length'] = 'Document must be less than 50 characters when document type is passport';
        }

        if (null !== $customer && isset($customer['document_type']) && 'passport' !== ($customer['document_type'] ?? null) && isset($customer['document']) && mb_strlen($customer['document']) > 16) {
            $this->errors['customer.document_length'] = 'Document must be less than 16 characters when document type is cpf or cnpj';
        }

        if (null !== $customer && isset($customer['gender']) && !in_array($customer['gender'], [
            'male',
            'female',
        ])) {
            $this->errors['customer.gender'] = 'Invalid gender on customer';
        }
    }
}
