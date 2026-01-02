<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\Log\Controller\RepeatSaleLogController;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class UpdateRepeatSaleLog extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();
		$entityTypeId = $item->getEntityTypeId();

		// @todo temporary only deals
		if ($entityTypeId !== \CCrmOwnerType::Deal)
		{
			return $result;
		}

		$itemBeforeSave = $this->getItemBeforeSave();
		if ($item->getStageSemanticId() === $itemBeforeSave?->remindActual('STAGE_SEMANTIC_ID'))
		{
			return $result;
		}

		RepeatSaleLogController::getInstance()->updateStageSemanticId(
			$item->getStageSemanticId(),
			new ItemIdentifier($entityTypeId, $item->getId()),
		);

		return $result;
	}
}
