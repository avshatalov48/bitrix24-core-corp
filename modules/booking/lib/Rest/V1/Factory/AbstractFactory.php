<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory;

use Bitrix\Main;

abstract class AbstractFactory
{
	abstract public function createFromRestFields(array $fields): mixed;

	public function validateRestFields(array $fields): Main\Result
	{
		return new Main\Result();
	}
}
