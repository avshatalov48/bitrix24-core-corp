<?php

namespace Bitrix\Crm\Controller\Validator\Timeline;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Validator;
use Bitrix\Crm\Timeline\Entity\Object\TimelineBinding;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;
use CCrmOwnerType;

class BindingExists implements Validator
{
	public function __construct(
		private readonly \CCrmOwnerType|string $CCrmOwnerType = CCrmOwnerType::class,
		private readonly TimelineEntry|string $timelineEntry = TimelineEntry::class,
	)
	{
	}

	/**
	 * @param TimelineBinding $value
	 * @return Result
	 * @throws ArgumentTypeException
	 */
	public function validate(mixed $value): Result
	{
		if (!($value instanceof TimelineBinding))
		{
			throw new ArgumentTypeException('value', TimelineBinding::class);
		}

		$timelineId = $value->getOwnerId();
		$entityTypeId = $value->getEntityTypeId();
		$entityId = $value->getEntityId();


		if($entityId <= 0 || !$this->CCrmOwnerType::IsDefined($entityTypeId))
		{
			return (new Result())->addError(ErrorCode::getOwnerNotFoundError());
		}

		if (!$this->timelineEntry::checkBindingExists($timelineId, $entityTypeId, $entityId))
		{
			return (new Result())->addError(ErrorCode::getNotFoundError());
		}

		return (new Result());
	}
}
