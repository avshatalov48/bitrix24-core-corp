<?php

namespace Bitrix\Crm\Controller\Validator\Entity;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Validator;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

abstract class AbstractPermission implements Validator
{
	protected UserPermissions $userPermissions;

	public function __construct(
		UserPermissions|null $userPermissions = null,
	)
	{
		$this->userPermissions = $userPermissions ?? Container::getInstance()->getUserPermissions();
	}

	/**
	 * @param ItemIdentifier $value
	 * @return Result
	 * @throws ArgumentTypeException
	 */
	public function validate(mixed $value): Result
	{
		if (!($value instanceof ItemIdentifier))
		{
			throw new ArgumentTypeException('value', ItemIdentifier::class);
		}

		if (!$this->checkPermissions($value->getEntityTypeId(), $value->getEntityId(), $value->getCategoryId()))
		{
			return (new Result())->addError(ErrorCode::getAccessDeniedError());
		}

		return (new Result());
	}

	abstract protected function checkPermissions(int $entityTypeId, int $entityId, int|null $categoryId = null): bool;
}
