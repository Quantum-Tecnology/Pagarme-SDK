<?php

declare(strict_types = 1);

/**
 * Sdk similar https://packagist.org/packages/mundipagg/mundiapi.
 */

namespace QuantumTecnology\PagarmeSDK;

use QuantumTecnology\PagarmeSDK\Recurrence\SubscriptionRepository;

class PaymentRepository
{
    private string $module;

    public static function card()
    {
        return new CardRepository();
    }

    public static function customer()
    {
        return new CustomerRepository();
    }

    public static function order()
    {
        return new OrderRepository();
    }

    public static function recurrence(string $module)
    {
        return match ($module) {
            'subscription' => new SubscriptionRepository(),
            // 'account'      => new AccountRepository(),
            default => false,
        };
    }
}
