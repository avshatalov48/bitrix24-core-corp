<?php

namespace Bitrix\Crm\Controller\Validator\Activity;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Validator;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

class ReadPermission implements Validator
{
	public function __construct(
		private readonly \CCrmActivity|string $CCrmActivity = \CCrmActivity::class,
	)
	{
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

		$result = new Result();

		if (!$this->CCrmActivity::CheckReadPermission($value->getEntityTypeId(), $value->getEntityId()))
		{
			return $result->addError(ErrorCode::getAccessDeniedError());
		}

		return $result;
	}
}
