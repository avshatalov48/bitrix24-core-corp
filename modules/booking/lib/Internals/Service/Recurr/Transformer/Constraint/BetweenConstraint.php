<?php

namespace Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

use Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

class BetweenConstraint extends Constraint
{

    protected $stopsTransformer = false;

    /** @var \DateTimeInterface */
    protected $before;

    /** @var \DateTimeInterface */
    protected $after;

    /** @var bool */
    protected $inc;

    /**
     * @param \DateTimeInterface $after
     * @param \DateTimeInterface $before
     * @param bool               $inc Include date if it equals $after or $before.
     */
    public function __construct(\DateTimeInterface $after, \DateTimeInterface $before, $inc = false)
    {
        $this->after  = $after;
        $this->before = $before;
        $this->inc    = $inc;
    }

    /**
     * Passes if $date is between $after and $before
     *
     * {@inheritdoc}
     */
    public function test(\DateTimeInterface $date)
    {
        if ($date > $this->before) {
            $this->stopsTransformer = true;
        }

        if ($this->inc) {
            return $date >= $this->after && $date <= $this->before;
        }

        return $date > $this->after && $date < $this->before;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getBefore()
    {
        return $this->before;
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
