<?php

declare(strict_types = 1);

/**
 * Sdk similar https://packagist.org/packages/mundipagg/mundiapi.
 */

namespace QuantumTecnology\PagarmeSDK;

use QuantumTecnology\PagarmeSDK\Recurrence\InvoiceRepository;
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

    public static function invoice(): InvoiceRepository
    {
        return new InvoiceRepository();
    }

    /*
     * ⚠️ Aditivo: `'invoice'` entra ao lado dos módulos existentes, e o
     * `default => false` continua intacto. O pacote já roda em produção em
     * mais de um serviço, e mudar o contrato de `recurrence()` quebraria quem
     * ainda não atualizou o lock.
     */
    public static function recurrence(string $module): SubscriptionRepository | PlanRepository | InvoiceRepository | false
    {
        return match ($module) {
            'subscription' => new SubscriptionRepository(),
            'plan'         => new PlanRepository(),
            'invoice'      => new InvoiceRepository(),
            default        => false,
        };
    }
}
