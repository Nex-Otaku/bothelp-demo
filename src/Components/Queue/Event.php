<?php

namespace App\Components\Queue;

use App\Helpers\Json;

class Event
{
    /** @var int */
    private $eventId;

    /** @var int */
    private $accountId;

    public function __construct(int $eventId, int $accountId)
    {
        $this->eventId   = $eventId;
        $this->accountId = $accountId;
    }

    public static function fromString(string $item): self
    {
        $decoded = (new Json())->decode($item);

        if (!is_array($decoded)
            || !array_key_exists('eventId', $decoded)
            || !array_key_exists('accountId', $decoded)) {
            throw new \RuntimeException("Не удалось декодировать элемент: {$item}");
        }

        return new Event($decoded['eventId'], $decoded['accountId']);
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