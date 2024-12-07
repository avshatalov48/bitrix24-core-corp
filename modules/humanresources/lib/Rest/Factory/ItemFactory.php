<?php

namespace Bitrix\HumanResources\Rest\Factory;

use Bitrix\Main;

abstract class ItemFactory
{
	public abstract function createFromRestFields(array $fields);

	public function validateRestFields(array $fields): Main\Result
	{
		return new Main\Result();
	}
}