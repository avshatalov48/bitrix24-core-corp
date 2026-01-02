<?php

namespace Bitrix\Crm\RepeatSale\Log;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\Log\Controller\RepeatSaleLogController;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Type\DateTime;

final class LogRecyclebinHelper
{
	use Singleton;

	public function getSlots(ItemIdentifier $itemIdentifier): array
	{
		$result = [];

		$controller = RepeatSaleLogController::getInstance();
		$logItem = $controller->getByItemIdentifier($itemIdentifier);
		if ($logItem)
		{
			$values = $logItem->collectValues();
			$values['CREATED_AT'] = $values['CREATED_AT']->toString();
			unset($values['UPDATED_AT']);

			$result['REPEAT_SALE_LOG'] = $values;
		}

		return $result;
	}

	public function restore(int $newEntityId, array $logItem): AddResult
	{
		$logItem['ENTITY_ID'] = $newEntityId;
		$logItem['CREATED_AT'] = (new DateTime($logItem['CREATED_AT']))->disableUserTime();

		return RepeatSaleLogTable::add($logItem);
	}
}
