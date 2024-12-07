<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Main\Result;

class DeleteEntityBadges extends Action
{
	public function process(Item $item): Result
	{
		if (!$this->isFinalizedWithStages($item))
		{
			return new Result();
		}

		$itemIdentifier = new ItemIdentifier($item->getEntityTypeId(), $item->getId(), $item->getCategoryId());

		Badge::deleteByEntity($itemIdentifier);

		Monitor::getInstance()->onBadgesSync($itemIdentifier);

		return new Result();
	}

	private function isFinalizedWithStages(Item $item): bool
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());

		return $factory?->isStagesEnabled() && $this->wasItemMovedToFinalStage($item);
	}

	private function wasItemMovedToFinalStage(Item $item): bool
	{
		if (!$item->hasField(Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		$previousStageId = (string)$this->getItemBeforeSave()?->remindActual(Item::FIELD_NAME_STAGE_ID);
		$currentStageId = (string)$item->getStageId();

		return ComparerBase::isMovedToFinalStage($item->getEntityTypeId(), $previousStageId, $currentStageId);
	}
}