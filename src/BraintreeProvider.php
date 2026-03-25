<?php

declare(strict_types=1);

namespace Payroad\Provider\Braintree;

use Braintree\Gateway;
use Payroad\Domain\Attempt\AttemptStatus;
use Payroad\Domain\Attempt\PaymentAttemptId;
use Payroad\Domain\Money\Money;
use Payroad\Domain\Payment\PaymentId;
use Payroad\Domain\PaymentFlow\Card\CardPaymentAttempt;
use Payroad\Domain\PaymentFlow\Card\CardRefund;
use Payroad\Domain\Refund\RefundId;
use Payroad\Port\Provider\Card\CardAttemptContext;
use Payroad\Port\Provider\Card\CardRefundContext;
use Payroad\Port\Provider\Card\ChargeResult;
use Payroad\Port\Provider\Card\TwoStepCardProviderInterface;
use Payroad\Port\Provider\WebhookEvent;
use Payroad\Provider\Braintree\Data\BraintreeCardAttemptData;
use Payroad\Provider\Braintree\Data\BraintreeCardRefundData;

final class BraintreeProvider implements TwoStepCardProviderInterface
{
    public function __construct(private readonly Gateway $gateway) {}

    public function supports(string $providerName): bool
    {
        return $providerName === 'braintree';
    }

    // ── Attempt initiation ────────────────────────────────────────────────────

    /**
     * Generates a Braintree client token for Drop-in UI.
     * The actual charge happens via sale() after the frontend obtains a nonce.
     * providerReference is set to 'bt_{attemptId}' so HandleWebhookUseCase
     * can locate the attempt by reference after the sale call.
     */
    public function initiateCardAttempt(
        PaymentAttemptId   $id,
        PaymentId          $paymentId,
        string             $providerName,
        Money              $amount,
        CardAttemptContext $context,
    ): CardPaymentAttempt {
        $clientToken = $this->gateway->clientToken()->generate();

        $data    = new BraintreeCardAttemptData(clientToken: $clientToken);
        $attempt = CardPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
        $attempt->setProviderReference('bt_' . $id->value);

        return $attempt;
    }

    public function initiateAttemptWithSavedMethod(
        PaymentAttemptId $id,
        PaymentId        $paymentId,
        string           $providerName,
        Money            $amount,
        string           $providerToken,
    ): CardPaymentAttempt {
        $result = $this->gateway->transaction()->sale([
            'amount'               => $this->toDecimal($amount),
            'paymentMethodToken'   => $providerToken,
            'options'              => ['submitForSettlement' => true],
        ]);

        if (!$result->success) {
            throw new \RuntimeException('Braintree sale failed: ' . $result->message);
        }

        $tx   = $result->transaction;
        $card = $tx->creditCardDetails;
        $data = new BraintreeCardAttemptData(
            clientToken:    '',
            transactionId:  $tx->id,
            last4:          $card->last4          ?? null,
            expiryMonth:    isset($card->expirationMonth) ? (int) $card->expirationMonth : null,
            expiryYear:     isset($card->expirationYear)  ? (int) $card->expirationYear  : null,
            cardBrand:      $card->cardType       ?? null,
            issuingCountry: $card->countryOfIssuance ?? null,
        );

        $attempt = CardPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
        $attempt->setProviderReference($tx->id);

        return $attempt;
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    public function initiateRefund(
        RefundId         $id,
        PaymentId        $paymentId,
        PaymentAttemptId $originalAttemptId,
        string           $providerName,
        Money            $amount,
        string           $originalProviderReference,
        CardRefundContext $context,
    ): CardRefund {
        $result = $this->gateway->transaction()->refund(
            $originalProviderReference,
            $this->toDecimal($amount),
        );

        if (!$result->success) {
            throw new \RuntimeException('Braintree refund failed: ' . $result->message);
        }

        $tx   = $result->transaction;
        $data = new BraintreeCardRefundData(status: $tx->status);

        $refund = CardRefund::create($id, $paymentId, $originalAttemptId, $providerName, $amount, $data);
        $refund->setProviderReference($tx->id);

        return $refund;
    }

    // ── Webhooks ──────────────────────────────────────────────────────────────

    public function parseIncomingWebhook(array $payload, array $headers): ?WebhookEvent
    {
        // Braintree webhooks are handled synchronously after sale() in BraintreeController.
        // This method is a no-op for the demo.
        return null;
    }

    public function chargeWithNonce(string $nonce, Money $amount): ChargeResult
    {
        $result = $this->gateway->transaction()->sale([
            'amount'             => $this->toDecimal($amount),
            'paymentMethodNonce' => $nonce,
            'options'            => ['submitForSettlement' => true],
        ]);

        if (!$result->success) {
            throw new \RuntimeException($result->message ?? 'Braintree charge failed');
        }

        return new ChargeResult(
            transactionId:  $result->transaction->id,
            newStatus:      AttemptStatus::SUCCEEDED,
            providerStatus: $result->transaction->status,
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function toDecimal(Money $amount): string
    {
        $precision = $amount->getCurrency()->precision;
        return number_format(
            $amount->getMinorAmount() / (10 ** $precision),
            $precision,
            '.',
            '',
        );
    }
}
