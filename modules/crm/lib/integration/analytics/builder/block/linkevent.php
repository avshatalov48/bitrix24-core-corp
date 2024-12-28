<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Block;

use Bitrix\Crm\Integration\Analytics\Dictionary;

final class LinkEvent extends BaseBlockEvent
{
	protected function buildCustomData(): array
	{
		$customData = parent::buildCustomData();
		$customData['event'] = Dictionary::EVENT_BLOCK_LINK;
		$customData['element'] = Dictionary::ELEMENT_ITEM_CONTACT_CENTER;

		return $customData;
	}
}