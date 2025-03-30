<?php

namespace Bitrix\Booking\Internals\Service\Recurr;

/**
 * Class DaySet is a container for a set and its meta.
 */
class DaySet
{
    /** @var array */
    public $set;

    /** @var int Day of year */
    public $start;

    /** @var int Day of year */
    public $end;

    /**
     * Constructor
     *
     * @param array $set   Set of days
     * @param int   $start Day of year of start day
     * @param int   $end   Day of year of end day
     */
    public function __construct($set, $start, $end)
    {
        $this->set   = $set;
        $this->start = $start;
        $this->end   = $end;
    }
}
