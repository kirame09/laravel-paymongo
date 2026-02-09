<?php

namespace Kirame\PayMongo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array createCustomer(array $attributes)
 * @method static array getCustomer(string $id)
 * @method static array createPaymentIntent(array $attributes)
 * @method static array retrievePaymentIntent(string $id)
 * @method static array createPaymentMethod(array $attributes)
 * @method static array attachPaymentMethod(string $intentId, string $methodId, ?string $clientKey = null)
 * @method static array createCheckoutSession(array $attributes)
 * @method static array retrieveCheckoutSession(string $id)
 * @method static array retrievePayment(string $id)
 * @method static array createRefund(string $paymentId, int $amount, string $reason = 'requested_by_customer')
 * @method static array createPlan(array $attributes)
 * @method static array createSubscription(array $attributes)
 * @method static array cancelSubscription(string $id)
 * @method static array updateSubscriptionPlan(string $id, string $planId)
 * @method static array getSubscription(string $id)
 * @method static array changePaymentMethod(string $subscriptionId, string $paymentMethodId)
 * @method static array payInvoice(string $invoiceId)
 * @method static array listWebhooks()
 * @method static array createWebhook(string $url, array $events)
 * @method static array enableWebhook(string $id)
 * @method static array disableWebhook(string $id)
 *
 * @see \Kirame\PayMongo\PayMongo
 */
class PayMongo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kirame\PayMongo\PayMongo::class;
    }
}
