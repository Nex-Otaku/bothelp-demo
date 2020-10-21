<?php

namespace App\Components\Queue;

class Event
{
    /** @var int */
    private $accountId;

    /** @var int */
    private $eventId;

    public function __construct(int $accountId, int $eventId)
    {
        $this->accountId = $accountId;
        $this->eventId = $eventId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }
}