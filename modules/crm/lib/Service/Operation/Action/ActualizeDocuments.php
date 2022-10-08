<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class ActualizeDocuments extends Action
{
	public function process(Item $item): Result
	{
		DocumentGeneratorManager::getInstance()->enqueueItemScheduledDocumentsForActualization(
			ItemIdentifier::createByItem($item),
			$this->getContext()->getUserId(),
		);

		return new Result();
	}
}
