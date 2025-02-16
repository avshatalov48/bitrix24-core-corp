<?php

namespace Bitrix\Crm\Security\Role\Validators;


use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use CCrmRole;

class RoleNameValidator
{
	use Singleton;

	public function validate(?string $name, int $roleId): Result
	{
		$result = new Result();

		$crmRole = new CCrmRole();
		$fields = ['NAME' => $name];
		$crmRole->CheckFields($fields, $roleId);
		$lastError = $crmRole->GetLastError();

		if (!empty($lastError))
		{
			$lastError = strip_tags($lastError);
			$result->addError(new Error($lastError, 'INVALID_ROLE_NAME'));
		}

		return $result;
	}
}
