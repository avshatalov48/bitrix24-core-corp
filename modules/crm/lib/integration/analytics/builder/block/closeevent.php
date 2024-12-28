<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\Block;

use Bitrix\Crm\Integration\Analytics\Dictionary;

final class CloseEvent extends BaseBlockEvent
{
	protected function buildCustomData(): array
	{
		$customData = parent::buildCustomData();
		$customData['event'] = Dictionary::EVENT_BLOCK_CLOSE;

		return $customData;
	}
}