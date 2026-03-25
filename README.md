# payroad/braintree-provider

Braintree card payment provider for the [Payroad](https://github.com/payroad/payroad-core) platform.

## Features

- Two-step card flow via Braintree Drop-in UI (server-side nonce submission)
- Authorize + capture / void (`CapturableCardProviderInterface`)
- Saved payment methods via Braintree vault (`TokenizingCardProviderInterface`)
- Webhook signature verification

## Requirements

- PHP 8.2+
- `payroad/payroad-core`
- `braintree/braintree_php`

## Installation

```bash
composer require payroad/braintree-provider
```

## Configuration

```yaml
# config/packages/payroad.yaml
payroad:
  providers:
    braintree:
      factory: Payroad\Provider\Braintree\BraintreeProviderFactory
      environment: '%env(BRAINTREE_ENVIRONMENT)%'   # sandbox | production
      merchant_id: '%env(BRAINTREE_MERCHANT_ID)%'
      public_key:  '%env(BRAINTREE_PUBLIC_KEY)%'
      private_key: '%env(BRAINTREE_PRIVATE_KEY)%'
```

## Payment flow

```
Frontend (Drop-in UI)                   Backend
─────────────────────────────────────────────────
POST /api/payments/card/initiate
  ← { clientToken, attemptId }
Drop-in renders, user enters card
  ← nonce
POST /api/payments/card/charge
  { nonce, attemptId }
  → chargeWithNonce(nonce)              Braintree transaction.sale()
  ← { status: succeeded }
                                    POST /webhooks/braintree
                                      (optional settlement webhook)
```

## Implemented interfaces

| Interface | Description |
|-----------|-------------|
| `TwoStepCardProviderInterface` | Server-side nonce submission via `chargeWithNonce()` |
| `CapturableCardProviderInterface` | `captureAttempt()` / `voidAttempt()` |
| `TokenizingCardProviderInterface` | Save and reuse payment methods via Braintree vault |
