<?php

declare(strict_types=1);

namespace StepupReadId\Domain\Session\Model;

use DateTimeImmutable;

final class SessionExpiryDate
{
    /** @var DateTimeImmutable */
    private $value;

    private function __construct(DateTimeImmutable $expiryDate)
    {
        $this->value = $expiryDate;
    }

    public static function fromISOString(string $expiryDateISO): SessionExpiryDate
    {
        return new self(new DateTimeImmutable($expiryDateISO));
    }

    public static function fromTimestamp(int $timestamp): SessionExpiryDate
    {
        $date = new DateTimeImmutable();

        return new self($date->setTimestamp($timestamp));
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    public function equals(SessionExpiryDate $other): bool
    {
        return $this->value->getTimestamp() === $other->value->getTimestamp();
    }
}
