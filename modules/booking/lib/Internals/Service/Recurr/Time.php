<?php

namespace Bitrix\Booking\Internals\Service\Recurr;

/**
 * Class Time is a storage container for a time of day.
 */
class Time
{
    /** @var int */
    public $hour;

    /** @var int */
    public $minute;

    /** @var int */
    public $second;

    public function __construct($hour, $minute, $second)
    {
        $this->hour   = $hour;
        $this->minute = $minute;
        $this->second = $second;
    }
}
