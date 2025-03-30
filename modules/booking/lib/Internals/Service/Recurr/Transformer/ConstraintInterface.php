<?php

namespace Bitrix\Booking\Internals\Service\Recurr\Transformer;

interface ConstraintInterface
{
    /**
     * @return bool
     */
    public function stopsTransformer();

    /**
     * @param \DateTimeInterface $date
     *
     * @return bool
     */
    public function test(\DateTimeInterface $date);
}
