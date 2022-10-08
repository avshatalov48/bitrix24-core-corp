<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Automation\Trigger\InvoiceTrigger;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Result;

class SmartInvoiceStatusChangedTrigger extends Operation\Action
{
	public function process(Item $item): Result
	{
		$itemBeforeSave = $this->getItemBeforeSave();
		if (
			$itemBeforeSave
			&& $itemBeforeSave->isChangedStageId()
			&& $item instanceof Item\SmartInvoice
		)
		{
			InvoiceTrigger::onSmartInvoiceStatusChanged($item);
		}

		return new Result();
	}
}
