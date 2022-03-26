<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Ml;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class UpdateMlScoring extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory || !$factory->isStagesSupported())
		{
			return $result;
		}

		$stage = $factory->getStage((string)$item->getStageId());
		if (!$stage)
		{
			return $result;
		}

		if (Ml\Scoring::isMlAvailable() && !PhaseSemantics::isFinal($stage->getSemantics()))
		{
			Ml\Scoring::queuePredictionUpdate(
				$item->getEntityTypeId(),
				$item->getId(),
				[
					'EVENT_TYPE' => Ml\Scoring::EVENT_ENTITY_UPDATE,
				],
			);
		}

		return $result;
	}
}
