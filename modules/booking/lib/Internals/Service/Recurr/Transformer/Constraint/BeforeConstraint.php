<?php

namespace Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

use Bitrix\Booking\Internals\Service\Recurr\Transformer\Constraint;

class BeforeConstraint extends Constraint
{

    protected $stopsTransformer = true;

    /** @var \DateTimeInterface */
    protected $before;

    /** @var bool */
    protected $inc;

    /**
     * @param \DateTimeInterface $before
     * @param bool               $inc Include date if it equals $before.
     */
    public function __construct(\DateTimeInterface $before, $inc = false)
    {
        $this->before = $before;
        $this->inc    = $inc;
    }

    /**
     * Passes if $date is before $before
     *
     * {@inheritdoc}
     */
    public function test(\DateTimeInterface $date)
    {
        if ($this->inc) {
            return $date <= $this->before;
        }

        return $date < $this->before;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @return bool
     */
    public function isInc()
    {
        return $this->inc;
    }
}
