<?php

declare(strict_types=1);

namespace Payroad\Provider\Braintree\Data;

use Payroad\Domain\Channel\Card\CardAttemptData;
use Payroad\Domain\Channel\Card\ThreeDSData;

final class BraintreeCardAttemptData implements CardAttemptData
{
    public function __construct(
        private readonly string  $clientToken,
        private readonly ?string $transactionId  = null,
        private readonly ?string $bin            = null,
        private readonly ?string $last4          = null,
        private readonly ?int    $expiryMonth    = null,
        private readonly ?int    $expiryYear     = null,
        private readonly ?string $cardholderName = null,
        private readonly ?string $cardBrand      = null,
        private readonly ?string $fundingType    = null,
        private readonly ?string $issuingCountry = null,
    ) {}

    /** Braintree client token for Drop-in UI initialization. */
    public function getClientToken(): ?string { return $this->clientToken; }

    /** Braintree transaction ID, set after successful sale(). */
    public function getTransactionId(): ?string { return $this->transactionId; }

    public function getBin(): ?string            { return $this->bin; }
    public function getLast4(): ?string          { return $this->last4; }
    public function getExpiryMonth(): ?int       { return $this->expiryMonth; }
    public function getExpiryYear(): ?int        { return $this->expiryYear; }
    public function getCardholderName(): ?string { return $this->cardholderName; }
    public function getCardBrand(): ?string      { return $this->cardBrand; }
    public function getFundingType(): ?string    { return $this->fundingType; }
    public function getIssuingCountry(): ?string { return $this->issuingCountry; }
    public function requiresUserAction(): bool   { return false; }
    public function getThreeDSData(): ?ThreeDSData { return null; }

    public function toArray(): array
    {
        return [
            'clientToken'   => $this->clientToken,
            'transactionId' => $this->transactionId,
            'bin'           => $this->bin,
            'last4'         => $this->last4,
            'expiryMonth'   => $this->expiryMonth,
            'expiryYear'    => $this->expiryYear,
            'cardBrand'     => $this->cardBrand,
            'fundingType'   => $this->fundingType,
            'issuingCountry'=> $this->issuingCountry,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            clientToken:    $data['clientToken'],
            transactionId:  $data['transactionId']  ?? null,
            bin:            $data['bin']             ?? null,
            last4:          $data['last4']           ?? null,
            expiryMonth:    $data['expiryMonth']     ?? null,
            expiryYear:     $data['expiryYear']      ?? null,
            cardBrand:      $data['cardBrand']       ?? null,
            fundingType:    $data['fundingType']     ?? null,
            issuingCountry: $data['issuingCountry']  ?? null,
        );
    }
}
