<?php

namespace Bitrix\Booking\Internals\Service\Recurr\Transformer;

abstract class Constraint implements ConstraintInterface
{
    protected $stopsTransformer = true;

    public function stopsTransformer()
    {
        return $this->stopsTransformer;
    }
}
