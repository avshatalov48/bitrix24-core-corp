<?php

namespace Bitrix\Crm\Controller\Validator\Activity;

use Bitrix\Crm\Activity\BindIdentifier;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Validator;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class BindingExists implements Validator
{
	public function __construct(
		private readonly \CCrmActivity|string $CCrmActivity = \CCrmActivity::class,
	)
	{

	}

	/**
	 * @param BindIdentifier $value
	 * @return Result
	 * @throws ArgumentTypeException
	 */
	public function validate(mixed $value): Result
	{
		if (!($value instanceof BindIdentifier))
		{
			throw new ArgumentTypeException('value', BindIdentifier::class);
		}

		$result = new Result();

		$owner = $value->getOwnerIdentifier();
		$activityId = $value->getActivityId();

		$bindingsData = $this->CCrmActivity::GetBindings($activityId);
		foreach ($bindingsData as $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === $owner->getEntityTypeId()
				&& (int)$binding['OWNER_ID'] === $owner->getEntityId()
			)
			{
				return $result;
			}
		}

		return $result->addError(ErrorCode::getOwnerNotFoundError());
	}
}
