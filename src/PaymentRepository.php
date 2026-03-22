<?php

declare(strict_types = 1);

/**
 * Sdk similar https://packagist.org/packages/mundipagg/mundiapi.
 */

namespace QuantumTecnology\PagarmeSDK;

use QuantumTecnology\PagarmeSDK\Recurrence\PlanRepository;
use QuantumTecnology\PagarmeSDK\Recurrence\SubscriptionRepository;

class PaymentRepository
{
    public static function card(): CardRepository
    {
        return new CardRepository();
    }

    public static function customer(): CustomerRepository
    {
        return new CustomerRepository();
    }

    public static function order(): OrderRepository
    {
        return new OrderRepository();
    }

    public static function plan(): PlanRepository
    {
        return new PlanRepository();
    }

    public static function recurrence(string $module): SubscriptionRepository | PlanRepository | false
    {
        return match ($module) {
            'subscription' => new SubscriptionRepository(),
            'plan'         => new PlanRepository(),
            default => false,
        };
    }
}
