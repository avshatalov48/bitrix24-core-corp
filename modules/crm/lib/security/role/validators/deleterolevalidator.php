<?php

namespace Bitrix\Crm\Security\Role\Validators;


use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class DeleteRoleValidator
{
	use Singleton;

	public function validate(int $roleId): Result
	{
		$result = new Result();

		if ($roleId <= 0)
		{
			$result->addError(new Error('Role ID value must higher then 0'));
		}

		return $result;
	}
}