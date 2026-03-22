<?php

declare(strict_types = 1);

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumTecnology\PagarmeSDK;

use Illuminate\Support\Facades\Http;
use QuantumTecnology\PagarmeSDK\Exceptions\PaymentException;

class OrderRepository extends BaseRepository
{
    private array $payments = [];
    private array $customer = [];
    private array $items    = [];

    public function __construct()
    {
        $this->urlApi = config('services.pagarme.url');
        $this->authorization = base64_encode(config('services.pagarme.access_token') . ':');
    }

    public function create(array $data = []): static
    {
        if (0 == count($data)) {
            $data = [
                'customer' => $this->customer,
                'items'    => $this->items,
                'payments' => $this->payments,
            ];
        }

        $response = Http::withToken($this->authorization, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->urlApi . '/orders', $data);

        $this->success   = $response->successful();
        $this->http_code = $response->status();

        if (!$response->successful()) {
            $this->message = $response->object()->message ?? 'Order creation failed';
            $this->errors  = (array) ($response->object()->errors ?? []);

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
    }

    public function setPayment(string $paymentMethod, object | array $data): static
    {
        $data = is_array($data) ? (object) $data : $data;

        if ('credit_card' === $paymentMethod) {
            throw_if(!isset($data->card->cvv), new PaymentException('O campo cvv é obrigatório.'));

            if (!isset($data->installments)) {
                $data->installments = 1;
            }

            if (!isset($data->statement_descriptor)) {
                $data->statement_descriptor = 'Deposito';
            }

            if (isset($data->card_id)) {
                $card = [
                    'card_id' => $data->card_id,
                    'card'    => [
                        'cvv' => $data->card->cvv,
                    ],
                ];
            } else {
                $card = [
                    'card' => [
                        'number'          => $data->card->number,
                        'holder_name'     => $data->card->holder_name,
                        'holder_document' => $data->card->holder_document,
                        'exp_month'       => $data->card->exp_month,
                        'exp_year'        => $data->card->exp_year,
                        'cvv'             => $data->card->cvv,
                        'brand'           => $data->card->brand,
                        'billing_address' => $data->card->billing_address,
                        'options'         => $data->card->options,
                    ],
                ];
            }

            $this->payments[] = [
                'payment_method' => 'credit_card',
                'credit_card'    => [
                    'installments'         => $data->installments,
                    'statement_descriptor' => $data->statement_descriptor,
                ] + $card,
            ];
        }

        return $this;
    }

    public function setCustomer(object | array $data): static
    {
        $data = is_array($data) ? (object) $data : $data;

        $this->customer = [
            'name'          => $data->name,
            'email'         => $data->email,
            'document'      => $data->document,
            'document_type' => $data->document_type,
            'type'          => $data->type,
        ];

        return $this;
    }

    public function setItem(object | array $data): static
    {
        $data = is_array($data) ? (object) $data : $data;

        $this->items[] = [
            'code'        => $data->code,
            'amount'      => $data->amount,
            'description' => $data->description,
            'quantity'    => $data->quantity,
        ];

        return $this;
    }
}
