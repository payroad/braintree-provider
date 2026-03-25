<?php

declare(strict_types=1);

namespace Payroad\Provider\Braintree\Data;

use Payroad\Port\Provider\Card\CardRefundData;

final readonly class BraintreeCardRefundData implements CardRefundData
{
    public function __construct(
        /**
         * Braintree refund transaction status at creation time
         * (e.g. "submitted_for_settlement").
         */
        private string  $status,
        /** Braintree does not expose an ARN at refund creation time. */
        private ?string $acquirerReferenceNumber = null,
    ) {}

    public function getReason(): ?string                  { return null; }
    public function getAcquirerReferenceNumber(): ?string { return $this->acquirerReferenceNumber; }
    public function getStatus(): string                   { return $this->status; }

    public function toArray(): array
    {
        return [
            'status'                  => $this->status,
            'acquirerReferenceNumber' => $this->acquirerReferenceNumber,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status:                  $data['status']                  ?? '',
            acquirerReferenceNumber: $data['acquirerReferenceNumber'] ?? null,
        );
    }
}
