<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;

final class ProcessInventoryManagementOnUpdate extends Crm\Reservation\Actions\ProcessInventoryManagement
{
	public function process(Crm\Item $item): Main\Result
	{
		$result = new Main\Result();

		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			$result->addError(new Main\Error('Item before save is required'));
			return $result;
		}

		if ($this->isMovedToFinalStage($item, $itemBeforeSave))
		{
			$result = $this->processInternal($item);
		}

		return $result;
	}
}