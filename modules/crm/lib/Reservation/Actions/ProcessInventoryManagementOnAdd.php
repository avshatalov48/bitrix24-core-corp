<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;

final class ProcessInventoryManagementOnAdd extends Crm\Reservation\Actions\ProcessInventoryManagement
{
	public function process(Crm\Item $item): Main\Result
	{
		$result = new Main\Result();

		if ($this->isFinalStage($item))
		{
			$result = $this->processInternal($item);
		}

		return $result;
	}
}