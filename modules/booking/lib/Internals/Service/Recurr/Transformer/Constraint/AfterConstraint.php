<?php

namespace Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

use Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

class AfterConstraint extends Constraint
{
    protected $stopsTransformer = false;

    /** @var \DateTimeInterface */
    protected $after;

    /** @var bool */
    protected $inc;

    /**
     * @param \DateTimeInterface $after
     * @param bool               $inc Include date if it equals $after.
     */
    public function __construct(\DateTimeInterface $after, $inc = false)
    {
        $this->after = $after;
        $this->inc    = $inc;
    }

    /**
     * Passes if $date is after $after
     *
     * {@inheritdoc}
     */
    public function test(\DateTimeInterface $date)
    {
        if ($this->inc) {
            return $date >= $this->after;
        }

        return $date > $this->after;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @return bool
     */
    public function isInc()
    {
        return $this->inc;
    }
}
