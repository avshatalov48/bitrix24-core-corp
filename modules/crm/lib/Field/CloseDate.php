<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

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

		$previousStageId = $item->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = $item->getStageId();

		if ($previousStageId === $currentStageId)
		{
			return $result;
		}

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return $result->addError($this->getFactoryNotFoundError($item->getEntityTypeId()));
		}

		$previousStage = $factory->getStage((string)$previousStageId);
		// it's okay if there is no previous stage. For example, it could be a new item
		$previousStageSemantics = $previousStage ? $previousStage->getSemantics() : PhaseSemantics::PROCESS;

		$currentStage = $factory->getStage((string)$currentStageId);
		if (is_null($currentStage))
		{
			return $result;
		}

		if (
			$previousStageSemantics !== $currentStage->getSemantics()
			&& PhaseSemantics::isFinal($currentStage->getSemantics())
		)
		{
			$item->set($this->getName(), new Date());
		}

		return $result;
	}
}
