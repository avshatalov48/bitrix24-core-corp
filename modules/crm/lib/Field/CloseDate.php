<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class CloseDate extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();

		$isSetCurrentDateOnCompletionEnabled = (bool)($this->getSettings()['isSetCurrentDateOnCompletionEnabled'] ?? true);
		if (!$isSetCurrentDateOnCompletionEnabled)
		{
			return $result;
		}

		if (!$item->hasField(Item::FIELD_NAME_STAGE_ID))
		{
			return $result;
		}

		$previousStageId = (string)$item->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = (string)$item->getStageId();

		if (ComparerBase::isMovedToFinalStage($item->getEntityTypeId(), $previousStageId, $currentStageId))
		{
			// hack: some fields could be datetime, set them with time to maintain backward compatibility
			$item->set($this->getName(), new DateTime());
		}

		return $result;
	}
}
