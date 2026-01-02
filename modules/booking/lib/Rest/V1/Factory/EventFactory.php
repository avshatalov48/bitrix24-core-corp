<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory;

use Bitrix\Booking\Entity;

abstract class EventFactory extends AbstractFactory
{
	abstract public function createFromRestFields(array $fields): Entity\EventInterface;
}
