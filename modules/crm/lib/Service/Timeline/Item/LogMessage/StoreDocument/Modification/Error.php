<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;

use Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument\Modification;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;

abstract class Error extends Modification
{
	public function getContentBlocks(): ?array
	{
		return [
			'message' => (new Text())->setValue($this->getHistoryItemModel()->get('ERROR_MESSAGE'))
		];
	}
}
