<?php

namespace Bitrix\HumanResources\Internals\Command\Structure\Node\CreateDepartmentCommand\Strategy;
abstract class BaseAddUsersStrategy
{
	public function __construct()
	{
	}

	abstract public function execute(): \Bitrix\HumanResources\Result\PropertyResult;
}