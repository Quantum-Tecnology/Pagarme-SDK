<?php

/**
 * https://docs.pagar.me/reference/criar-cliente-1.
 */

namespace QuantumCode\PagarmeSDK;

use App\Enums\PaymentEnum;
use App\Exceptions\PaymentOperatorException;
use Illuminate\Support\Facades\Http;

class OrderRepository
{
    private string $url;
    public bool $success    = false;
    public string $message  = 'success';
    public array $errors    = [];
    private array $payments = [];
    private array $customer = [];
    private array $items    = [];
    public array|object $data;
    private string $token;

    public function __construct()
    {
        $this->url   = config('services.pagarme.url');
        $this->token = base64_encode(config('services.pagarme.access_token').':');
    }

    public function create(array $data = [])
    {
        if (0 == count($data)) {
            $data = [
                'customer' => $this->customer,
                'items'    => $this->items,
                'payments' => $this->payments,
            ];
        }

        $response = Http::withToken($this->token, 'Basic')
            ->retry(3, 2000, throw: false)
            ->acceptJson()
            ->asJson()
            ->post($this->url.'/orders', $data);

        $this->success = $response->successful();

        if (!$response->successful()) {
            $this->message = $response->object()->message;
            $this->errors  = (array) $response->object()->errors;

            return $this;
        }

        $this->data = $this->map($response->object());

        return $this;
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

    public function setPayment(string $paymentMethod, object|array $data)
    {
        $data = is_array($data) ? (object) $data : $data;

        if (PaymentEnum::METHOD_CREDITCARD === $paymentMethod) {
            throw_if(!isset($data->card->cvv), new PaymentOperatorException('O campo cvv é obrigatório.'));

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
                'payment_method'               => PaymentEnum::METHOD_CREDITCARD,
                PaymentEnum::METHOD_CREDITCARD => [
                    'installments'         => $data->installments,
                    'statement_descriptor' => $data->statement_descriptor,
                ] + $card,
            ];
        }

        return $this;
    }

    public function setCustomer(object|array $data)
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

    public function setItem(object|array $data)
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
