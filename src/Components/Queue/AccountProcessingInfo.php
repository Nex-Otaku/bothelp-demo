<?php

namespace App\Components\Queue;

use App\Helpers\Json;

class AccountProcessingInfo
{
    /** @var int */
    private $accountId;

    /** @var string */
    private $workerId;

    /** @var int */
    private $acquiredAt;

    public function __construct(
        int $accountId,
        string $workerId,
        int $acquiredAt
    ) {
        $this->accountId  = $accountId;
        $this->workerId   = $workerId;
        $this->acquiredAt = $acquiredAt;
    }

    public function toString(): string
    {
        return (new Json())->encode(
            [
                'accountId'  => $this->accountId,
                'workerId'   => $this->workerId,
                'acquiredAt' => $this->acquiredAt,
            ]
        );
    }

    public static function fromString(string $item): self
    {
        $decoded = (new Json())->decode($item);

        if (!is_array($decoded)
            || !array_key_exists('accountId', $decoded)
            || !array_key_exists('workerId', $decoded)
            || !array_key_exists('acquiredAt', $decoded)) {
            throw new \RuntimeException("Не удалось декодировать элемент: {$item}");
        }

        return new self(
            $decoded['accountId'],
            $decoded['workerId'],
            $decoded['acquiredAt']
        );
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getWorkerId(): string
    {
        return $this->workerId;
    }

    public function getAcquiredAt(): int
    {
        return $this->acquiredAt;
    }
}