<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Validators;


use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\AccessCodeDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\AccessRightDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\UserGroupsData;
use Bitrix\Crm\Security\Role\Validators\RoleNameValidator;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class UserGroupDataValidator
{
	use Singleton;

	private RoleNameValidator $roleNameValidator;

	public function __construct()
	{
		$this->roleNameValidator = RoleNameValidator::getInstance();
	}

	/**
	 * @param UserGroupsData[] $userGroups
	 * @return Result
	 */
	public function validate(array $userGroups): Result
	{
		$result = new Result();

		foreach ($userGroups as $userGroup)
		{
			$roleRes = $this->validateRole($userGroup);
			if(!$roleRes->isSuccess())
			{
				$result->addErrors($roleRes->getErrors());
			}

			$accCodesRes = $this->validateAccessCodes($userGroup->accessCodes);
			if (!$accCodesRes->isSuccess())
			{
				$result->addErrors($accCodesRes->getErrors());
			}

			$accRightRes = $this->validateAccessRights($userGroup->accessRights);
			if (!$accRightRes->isSuccess())
			{
				$result->addErrors($accRightRes->getErrors());
			}
		}

		return $result;
	}

	/**
	 *
	 * @param AccessRightDTO[] $accessRights
	 * @return Result
	 */
	private function validateAccessRights(array $accessRights): Result
	{
		$result = new Result();

		foreach ($accessRights as $right)
		{
			if (empty($right->id))
			{
				$result->addError(new Error('Access right ID must not be empty or null'));
			}
		}

		return $result;
	}

	/**
	 * @param AccessCodeDTO[] $accessCodes
	 * @return Result
	 */
	private function validateAccessCodes(array $accessCodes): Result
	{
		$result = new Result();

		foreach ($accessCodes as $code)
		{
			if (empty($code->id))
			{
				$result->addError(new Error('Access code ID must not be empty or null'));
			}
		}

		return $result;
	}

	private function validateRole(UserGroupsData $userGroup): Result
	{
		$result = new Result();

		$name = $userGroup->title;
		$id = $userGroup->id ?: false;

		$roleResult = $this->roleNameValidator->validate($name, $id);
		if (!$roleResult->isSuccess())
		{
			foreach ($roleResult->getErrors() as $error)
			{
				$result->addError($error);
			}
		}

		return $result;
	}
}