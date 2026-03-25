<?php

declare(strict_types=1);

namespace Payroad\Provider\Braintree;

use Braintree\Gateway;
use Payroad\Port\Provider\ProviderFactoryInterface;

final class BraintreeProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): BraintreeProvider
    {
        return new BraintreeProvider(new Gateway([
            'environment' => $config['environment'],
            'merchantId'  => $config['merchant_id'],
            'publicKey'   => $config['public_key'],
            'privateKey'  => $config['private_key'],
        ]));
    }
}
