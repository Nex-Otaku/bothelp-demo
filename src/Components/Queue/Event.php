<?php

namespace App\Components\Queue;

use App\Helpers\Json;

class Event
{
    /** @var int */
    private $accountId;

    /** @var int */
    private $eventId;

    public function __construct(int $accountId, int $eventId)
    {
        $this->accountId = $accountId;
        $this->eventId   = $eventId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function toString(): string
    {
        return (new Json())->encode(
            [
                'eventId'   => $this->eventId,
                'accountId' => $this->accountId,
            ]
        );
    }
}