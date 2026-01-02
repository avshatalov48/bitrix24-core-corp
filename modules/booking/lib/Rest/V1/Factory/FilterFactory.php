<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory;

abstract class FilterFactory extends AbstractFactory
{
	abstract public function createFromRestFields(array $fields): array;
}
